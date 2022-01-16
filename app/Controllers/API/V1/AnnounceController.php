<?php

namespace app\Controllers\API\V1;

use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\LevelId;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CVersion;
use app\Common\IPEndPoint;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\InternalServerErrorResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use app\Models\LevelRecord;
use function app\Controllers\API\ctype_alnum;

class AnnounceController
{
    public function announce(Request $request): Response
    {
        global $bssbConfig;

        // -------------------------------------------------------------------------------------------------------------
        // Pre-flight checks

        $isClientRequest = $request->getIsValidModClientRequest();
        $isDediRequest = $request->getIsValidBeatDediRequest();

        if ((!$isClientRequest && !$isDediRequest)
            || !$request->getIsJsonRequest()
            || $request->method !== "POST") {
            return new BadRequestResponse();
        }

        $modClientInfo = $request->getModClientInfo();

        // -------------------------------------------------------------------------------------------------------------
        // Read input

        $input = $request->getJson();

        $ownerId = $input['OwnerId'] ?? "";
        $serverType = $input['ServerType'] ?? null;
        $hostSecret = $input['HostSecret'] ?? null;
        $endpoint = IPEndPoint::tryParse($input['Endpoint'] ?? null);
        $managerId = $input['ManagerId'] ?? "";

        if (empty($ownerId) || $ownerId === "SERVER_MESSAGE") {
            // Owner ID is always required, and can't be "SERVER_MESSAGE" as we reserve that for our own use
            return new BadRequestResponse();
        }

        $lastLobbyStateNormalized = null;
        $isNewGame = false;

        /**
         * @var $game HostedGame|null
         */
        if ($hostSecret) {
            // Default: identify lobbies uniquely by Host ID + Host Secret
            $game = HostedGame::query()
                ->where('owner_id = ?', $ownerId)
                ->andWhere('host_secret = ?', $hostSecret)
                ->querySingleModel();
        } else {
            // Fallback, for backwards compatibility with p2p games: identify by Host ID + an explicitly missing secret
            $game = HostedGame::query()
                ->where('owner_id = ?', $ownerId)
                ->andWhere('host_secret IS NULL')
                ->querySingleModel();
        }

        if (!$game) {
            // Create new
            $game = new HostedGame();
            $isNewGame = true;
        }

        $game->serverCode = strtoupper($input['ServerCode'] ?? "");
        $game->gameName = trim($input['GameName'] ?? "");
        $game->ownerId = $ownerId;
        $game->ownerName = $input['OwnerName'] ?? "Unknown";
        $game->playerCount = intval($input['PlayerCount'] ?? 0);
        $game->playerLimit = intval($input['PlayerLimit'] ?? 0);
        $game->isModded = intval($input['IsModded'] ?? 0) === 1;
        $game->lobbyState = intval($input['LobbyState'] ?? MultiplayerLobbyState::None);
        $game->platform = isset($input['Platform']) ? strtolower($input['Platform']) : ModPlatformId::UNKNOWN;

        if (isset($input['MasterServerEp']))
        {
            $epParts = explode(':', $input['MasterServerEp'], 2);

            $game->masterServerHost = $epParts[0] ?? null;
            $game->masterServerPort = isset($epParts[1]) ? intval($epParts[1]) : null;
        }
        else
        {
            $game->masterServerHost = $input['MasterServerHost'] ?? null;
            $game->masterServerPort = isset($input['MasterServerPort']) ? intval($input['MasterServerPort']) : null;
        }

        $game->modName = $modClientInfo->modName;
        $game->modVersion = $modClientInfo->assemblyVersion;
        $game->gameVersion = $modClientInfo->beatSaberVersion;
        $game->serverType = $serverType;
        $game->hostSecret = $hostSecret;
        $game->endpoint = $endpoint;
        $game->managerId = $managerId;

        $mpExVersion = isset($input['MpExVersion']) ? new CVersion($input['MpExVersion']) : null;
        $game->mpExVersion = $mpExVersion ? $mpExVersion->toString(3) : null;

        if (!empty($input['LevelId'])) {
            $game->levelId = LevelId::cleanLevelHash($input['LevelId']);
            $game->songName = $input['SongName'] ?? null;
            $game->songAuthor = $input['SongAuthor'] ?? null;
        }

        if (!empty($input['LevelId']) || $game->getIsQuickplay()) {
            $game->difficulty = isset($input['Difficulty']) ? intval($input['Difficulty']) : null;
        }

        // -------------------------------------------------------------------------------------------------------------
        // Validation and processing

        if (isset($bssbConfig['master_server_blacklist']) && in_array($game->masterServerHost, $bssbConfig['master_server_blacklist'])) {
            // Master server is blacklisted, do not allow announcing
            return new Response(403, 'leaker stop leaking');
        }

        if (empty($game->gameName)) {
            $game->gameName = "Untitled Beat Game";
        }

        if (!$game->isModded) {
            // For some reason the "modded" flag doesn't always get set, so apply failsafes
            if ($game->mpExVersion != null) {
                // MpEx version provided, must be modded
                $game->isModded = true;
            }
        }

        $isModernMultiplayer = $modClientInfo->beatSaberVersion->greaterThanOrEquals(new CVersion("1.16.3"));
        if ($game->isModded && $isModernMultiplayer && $game->getIsOfficial()) {
            // Official games *cannot* be modded anymore as of 1.16.3
            $game->isModded = false;
        }

        if (!$game->getIsQuickplay() && (empty($game->serverCode) || strlen($game->serverCode) !== 5 || !\ctype_alnum($game->serverCode))) {
            // Server code should always be alphanumeric, 5 characters, e.g. "ABC123"
            return new BadRequestResponse();
        }

        $maxPlayerLimit = $game->getMaxPlayerLimit();
        if ($game->playerLimit <= 0 || $game->playerLimit > $maxPlayerLimit) {
            $game->playerLimit = $maxPlayerLimit;
        }

        $minPlayerCount = $game->getMinPlayerCount();
        if ($game->playerCount < $minPlayerCount) {
            $game->playerCount = $minPlayerCount;
        } else if ($game->playerCount > $game->playerLimit) {
            $game->playerCount = $game->playerLimit;
        }

        if ($game->masterServerHost) {
            if ($game->masterServerHost === "oculus.production.mp.beatsaber.com") {
                $game->platform = ModPlatformId::OCULUS;
            } else if ($game->masterServerHost === "steam.production.mp.beatsaber.com") {
                $game->platform = ModPlatformId::STEAM;
            }
        }

        if ($game->getIsBeatDedi() && !$isDediRequest) {
            // Trying to announce a BeatDedi game, but does not appear to originate from BeatDedi
            return new BadRequestResponse();
        }

        if ($game->getIsQuickplay() && empty($game->hostSecret)) {
            // Trying to announce a Quickplay game, but no host secret provided
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Level data sync

        if ($game->levelId && $game->songName) {
            // Update base data
            $levelRecord = LevelRecord::syncFromAnnounce($game->levelId, $game->songName, $game->songAuthor);

            // Update play count stat if we transitioned from lobby to the level
            $gameWasInLobby = ($lastLobbyStateNormalized === null
                || $lastLobbyStateNormalized === MultiplayerLobbyState::LobbySetup
                || $lastLobbyStateNormalized === MultiplayerLobbyState::LobbyCountdown);

            $newLobbyStateNormalized = $game->getAdjustedState();

            $gameIsRunningOrStarting = ($newLobbyStateNormalized === MultiplayerLobbyState::GameRunning
                || $newLobbyStateNormalized === MultiplayerLobbyState::GameStarting);

            if ($gameWasInLobby && $gameIsRunningOrStarting) {
                $levelRecord->incrementPlayStat();
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Game name overrides

        if ($game->serverType === HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY) {
            $difficultyName = LevelDifficulty::describe($game->difficulty);
            if ($game->getIsOfficial()) {
                $game->gameName = "Official Quick Play - {$difficultyName}";
            } else {
                $game->gameName = "Unofficial Quick Play - {$difficultyName}";
            }
        } else if ($game->serverType === HostedGame::SERVER_TYPE_BEATTOGETHER_QUICKPLAY) {
            $difficultyName = LevelDifficulty::describe($game->difficulty);
            $game->gameName = "BeatTogether Quick Play - {$difficultyName}";
        }

        // -------------------------------------------------------------------------------------------------------------
        // Write

        // Update timestamps
        $now = new \DateTime('now');

        if (empty($game->firstSeen)) {
            $game->firstSeen = $now;
        }

        $game->lastUpdate = $now;

        // Resurrect the game if needed (remove endedAt flag)
        $isResurrectedGame = false;
        if ($game->endedAt) {
            $game->endedAt = null;
            $isResurrectedGame = true;
        }

        // Discard boring games
        if ($game->getIsUninteresting()) {
            $game->endedAt = new \DateTime('now');
            $isResurrectedGame = false;
        }

        // Insert or update
        $saveOk = $game->save();

        if ($saveOk && $game->id && $game->serverType === HostedGame::SERVER_TYPE_PLAYER_HOST) {
            // Mark any older games from same P2P owner as "ended"
            HostedGame::query()
                ->update()
                ->set("ended_at = ?", $now)
                ->where('ended_at IS NULL')
                ->andWhere('owner_id = ?', $ownerId)
                ->andWhere('id != ? AND id < ?', $game->id, $game->id)
                ->execute();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Sync player list

        if ($saveOk) {
            $playerList = $input['Players'] ?? null;

            if ($playerList && is_array($playerList)) {
                $playerIndexesKnown = [];

                // Add or update players
                foreach ($playerList as $playerItem) {
                    $sortIndex = intval($playerItem['SortIndex'] ?? -999);
                    $userId = $playerItem['UserId'] ?? null;
                    $userName = $playerItem['UserName'] ?? null;
                    $isHost = intval($playerItem['IsHost'] ?? 0) === 1;
                    $isAnnouncer = intval($playerItem['IsAnnouncer'] ?? 0) === 1;
                    $latency = floatval($playerItem['Latency'] ?? 0);

                    if ($sortIndex === -1) {
                        // -1 slot is used for dedicated servers only
                        if (!$isHost) {
                            continue;
                        }
                        if (!$userName) {
                            $userName = "Dedicated Server";
                        }
                    }

                    if ($sortIndex < -1 || !$userId || !$userName) {
                        // Invalid entry, missing minimum data
                        continue;
                    }

                    $playerRecord = new HostedGamePlayer();
                    $playerRecord->hostedGameId = $game->id;
                    $playerRecord->sortIndex = $sortIndex;

                    if ($game->id) {
                        // Replace existing player on this index, if it exists
                        $existingPlayerRecord = HostedGamePlayer::query()
                            ->where('hosted_game_id = ?', $game->id)
                            ->andWhere('sort_index = ?', $sortIndex)
                            ->querySingleModel();
                        if ($existingPlayerRecord) {
                            $playerRecord = $existingPlayerRecord;
                        }
                    }

                    $playerRecord->userId = $userId;
                    $playerRecord->userName = $userName;
                    $playerRecord->isHost = $isHost;
                    $playerRecord->isAnnouncer = $isAnnouncer;
                    $playerRecord->latency = $latency;
                    $playerRecord->isConnected = true;
                    $playerRecord->save();

                    $playerIndexesKnown[] = $sortIndex;
                }

                if (!$isNewGame) {
                    // This is an update: mark players that may have disconnected
                    if (!empty($playerIndexesKnown)) {
                        HostedGamePlayer::query()
                            ->update()
                            ->set('is_connected = 0')
                            ->where('hosted_game_id = ?', $game->id)
                            ->andWhere('sort_index NOT IN (?)', $playerIndexesKnown)
                            ->execute();
                    }
                }
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        if ($saveOk) {
            return new JsonResponse([
                "success" => true,
                "key" => $game->getHashId()
            ]);
        }

        return new InternalServerErrorResponse("Could not write game to database");
    }
}