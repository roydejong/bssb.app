<?php

namespace app\Controllers\API\V1;

use app\BeatSaber\GameVersionAliases;
use app\BeatSaber\MasterServer;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CVersion;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;
use app\Models\Joins\HostedGameLevelRecord;
use app\Models\SystemConfig;

class BrowseController
{
    public const PAGE_SIZE = 6;
    public const PAGE_SIZE_MAX = 12;

    public function browse(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Pre-flight checks

        $mci = $request->getModClientInfo();

        // -------------------------------------------------------------------------------------------------------------
        // Read input

        $searchQuery = $request->queryParams['query'] ?? null;
        $platformConstraint = $request->queryParams['platform'] ?? null;
        $isVanilla = intval($request->queryParams['vanilla'] ?? 0) === 1;
        $includeLevel = intval($request->queryParams['includeLevel'] ?? 0) === 1;

        $filterFull = intval($request->queryParams['filterFull'] ?? 0) === 1;
        $filterInProgress = intval($request->queryParams['filterInProgress'] ?? 0) === 1;
        $filterModded = $isVanilla || intval($request->queryParams['filterModded'] ?? 0) === 1;
        $filterVanilla = intval($request->queryParams['filterVanilla'] ?? 0) === 1;
        $filterServerType = $request->queryParams['filterServerType'] ?? null;
        $filterQuickPlay = intval($request->queryParams['filterQuickPlay'] ?? 0) === 1;

        // -------------------------------------------------------------------------------------------------------------
        // Query

        $offset = intval($request->queryParams['offset'] ?? 0);
        $limit = intval($request->queryParams['limit'] ?? 0);

        if ($limit <= 0) {
            $limit = self::PAGE_SIZE;
        }

        if ($limit > self::PAGE_SIZE_MAX) {
            $limit = self::PAGE_SIZE_MAX;
        }

        // -------------------------------------------------------------------------------------------------------------
        // Query preparation

        $baseQuery = HostedGameLevelRecord::query()
            ->select("hosted_games.*, lr.beatsaver_id, lr.cover_url, lr.name AS level_name")
            ->from("hosted_games")
            ->where("is_stale = 0")
            ->andWhere("ended_at IS NULL")
            ->leftJoin("level_records lr ON (lr.level_id = hosted_games.level_id)")
            ->orderBy("player_count >= player_limit ASC, player_count > 1 ASC, player_limit DESC, hosted_games.id DESC");

        if ($mci->beatSaberVersion) {
            // Only show results from same game version
            $compatibleGameVersions = GameVersionAliases::getAliasesFor($mci->beatSaberVersion, true);
            $baseQuery->andWhere('game_version IN (?)', $compatibleGameVersions);
        }

        // Search query
        if (!empty($searchQuery)) {
            $likeParam = "%{$searchQuery}%";
            $baseQuery->andWhere('(game_name LIKE ? OR hosted_games.song_name LIKE ? OR hosted_games.song_author LIKE ?)',
                $likeParam, $likeParam, $likeParam);
        }

        // Hide custom master servers if we are below mod version 0.2
        $officialMasterServerLike = "%.mp.beatsaber.com";
        $supportsCustomMasterServers = $mci->getSupportsCustomMasterServers();

        if (!$supportsCustomMasterServers) {
            // Don't show games wih custom master servers
            $baseQuery->andWhere("(master_server_host IS NULL OR master_server_host LIKE ?)",
                $officialMasterServerLike);
        }

        // Filter: hide full games
        if ($filterFull) {
            $baseQuery->andWhere('player_count < player_limit');
        }

        // Filter: hide games in progress
        if ($filterInProgress) {
            // NB: This mostly works for <= 1.16.2 clients too as "LobbyCountdown" is "GameStarting" for them
            $baseQuery->andWhere('lobby_state <= ?', MultiplayerLobbyState::LobbyCountdown);
        }

        // Filter: Vanilla mode; hide all modded, MpEx games
        if ($filterModded) {
            $baseQuery->andWhere('is_modded = 0');
        } else if ($filterVanilla) {
            $baseQuery->andWhere('is_modded = 1');
        }

        // Hide Quick Play games if unsupported by mod version or requested by filter
        if (!$mci->getSupportsQuickPlayServers() || $filterQuickPlay) {
            $baseQuery->andWhere('server_type IS NULL OR server_type NOT IN (?)',
                [HostedGame::SERVER_TYPE_BEATTOGETHER_QUICKPLAY, HostedGame::SERVER_TYPE_BEATDEDI_QUICKPLAY,
                    HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY]);
        }

        // Hide Official/Vanilla games if we are on modded Quest
        if ($mci->getIsServerBrowserQuest()) {
            $baseQuery->andWhere('master_server_host NOT LIKE ?',
                '%' . MasterServer::OFFICIAL_HOSTNAME_SUFFIX);
        }

        // Server type filter (0.7.0+)
        if ($filterServerType) {
            $baseQuery->andWhere('server_type = ?', $filterServerType);
        }

        // Hide official (gamelift) quick play lobbies without a distinct host secret for all clients >=1.19.1
        if ($mci->beatSaberVersion && $mci->beatSaberVersion->greaterThanOrEquals(new CVersion("1.19.1"))) {
            $baseQuery->andWhere('server_type != ? OR host_secret != owner_id',
                HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY);
        }

        // Hide direct connect servers for <1.1.0 mod clients
        $supportsDirectConnect = $mci->getSupportsDirectConnect();

        if (!$supportsDirectConnect) {
            $baseQuery->andWhere('master_server_host IS NOT NULL');
        }

        // -------------------------------------------------------------------------------------------------------------
        // Query actual

        // Count
        $countQuery = clone $baseQuery;
        $totalCount = intval($countQuery->select()->count()->querySingleValue());

        // Data
        $gamesSz = [];

        if ($totalCount > 0) {
            /**
             * @var $games HostedGame[]
             */
            $games = $baseQuery
                ->offset($offset)
                ->limit($limit)
                ->queryAllModels();

            foreach ($games as $game) {
                $gamesSz[] = $game->jsonSerialize(false, $includeLevel);
            }
        }

        $sysConfig = SystemConfig::fetchInstance();

        return new JsonResponse([
            "Count" => $totalCount,
            "Offset" => $offset,
            "Limit" => $limit,
            "Lobbies" => $gamesSz,
            "Message" => $sysConfig->serverMessage
        ]);
    }
}