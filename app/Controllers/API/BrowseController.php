<?php

namespace app\Controllers\API;

use app\BeatSaber\ModPlatformId;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\Joins\HostedGameLevelRecord;

class BrowseController
{
    private const PAGE_SIZE = 6;

    public function browse(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Pre-flight checks

        if (!$request->getIsValidModClientRequest()) {
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Read input

        $searchQuery = $request->queryParams['query'] ?? null;
        $platformConstraint = $request->queryParams['platform'] ?? null;
        $isVanilla = intval($request->queryParams['platform'] ?? 0) === 1;

        // -------------------------------------------------------------------------------------------------------------
        // Query

        $offset = intval($request->queryParams['offset'] ?? 0);
        $limit = self::PAGE_SIZE;

        if ($request->queryParams['limit'] ?? null === "max") {
            $limit = 100;
        }

        // -------------------------------------------------------------------------------------------------------------
        // Query preparation

        $updateCutoff = new \DateTime('now');
        $updateCutoff->modify('-10 minutes');

        $baseQuery = HostedGameLevelRecord::query()
            ->select("hosted_games.*, lr.beatsaver_id, lr.cover_url, lr.name AS level_name")
            ->from("hosted_games")
            ->where("last_update >= ?", $updateCutoff)
            ->leftJoin("level_records lr ON (lr.level_id = hosted_games.level_id)")
            ->orderBy("hosted_games.id DESC");

        // Search query
        if (!empty($searchQuery)) {
            $likeParam = "%{$searchQuery}%";
            $baseQuery->andWhere('(game_name LIKE ? OR hosted_games.song_name LIKE ? OR hosted_games.song_author LIKE ?)',
                $likeParam, $likeParam, $likeParam);
        }

        // Platform constraint
        $excludePlatformId = null;

        switch ($platformConstraint) {
            case ModPlatformId::OCULUS:
                // Oculus shouldn't see Steam
                $excludePlatformId = ModPlatformId::STEAM;
                break;
            case ModPlatformId::STEAM:
                // Steam should see Oculus
                $excludePlatformId = ModPlatformId::OCULUS;
                break;
        }

        if ($excludePlatformId) {
            // Filter games that are on "$excludePlatform", UNLESS they're using non-official servers
            $officialMasterServerLike = "%.mp.beatsaber.com";
            $baseQuery->andWhere("(platform != ? OR (master_server_host IS NOT NULL AND master_server_host NOT LIKE ?))",
                $excludePlatformId, $officialMasterServerLike);
        }

        // Vanilla mode (no MpEx games)
        if ($isVanilla) {
            $baseQuery->andWhere('is_modded = 0');
        }

        // -------------------------------------------------------------------------------------------------------------
        // Query actual

        // Count
        $countQuery = clone $baseQuery;
        $totalCount = intval($countQuery->select()->count()->querySingleValue());

        // Data
        $games = [];

        if ($totalCount > 0) {
            $games = $baseQuery
                ->offset($offset)
                ->limit($limit)
                ->queryAllModels();
        }

        return new JsonResponse([
            "Count" => $totalCount,
            "Offset" => $offset,
            "Limit" => $limit,
            "Lobbies" => $games
        ]);
    }
}