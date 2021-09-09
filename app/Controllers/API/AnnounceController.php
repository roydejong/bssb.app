<?php

namespace app\Controllers\API;

use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\LevelId;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CString;
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

class AnnounceController
{
    public function announce(Request $request): Response
    {
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

        if (empty($ownerId) || $ownerId === "SERVER_MESSAGE") {
            // Owner ID is always required, and can't be "SERVER_MESSAGE" as we reserve that for our own use
            return new BadRequestResponse();
        }

        /**
         * @var $game HostedGame|null
         */
        $game = null;
        $lastLobbyStateNormalized = null;
        $isNewGame = false;
        $isQuickplay = false;

        if ($serverType === HostedGame::SERVER_TYPE_VANILLA_QUICKPLAY
            || $serverType === HostedGame::SERVER_TYPE_BEATTOGETHER_QUICKPLAY)  {
            // Classic Quickplay mode: identify games by their secrets
            $isQuickplay = true;

            /**
             * @var $gameBySecret HostedGame|null
             */
            $gameBySecret = HostedGame::query()
                ->where('server_type = ?', $serverType)
                ->andWhere('host_secret = ?', $hostSecret)
                ->querySingleModel();

            if ($gameBySecret) {
                // Replace existing
                $game = $gameBySecret;
                $lastLobbyStateNormalized = $game->getAdjustedState();
            }
        } else {
            // Normal mode: identify games by their owners
            /**
             * @var $gameByOwner HostedGame|null
             */
            $gameByOwner = HostedGame::query()
                ->where('owner_id = ?', $ownerId)
                ->querySingleModel();

            if ($gameByOwner) {
                // Replace existing
                $game = $gameByOwner;
                $lastLobbyStateNormalized = $game->getAdjustedState();
            }
        }

        if (!$game)
        {
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

        if (empty($game->gameName)) {
            $game->gameName = "Untitled Beat Game";
        }

        if (!empty($input['LevelId'])) {
            $game->levelId = LevelId::cleanLevelHash($input['LevelId']);
            $game->songName = $input['SongName'] ?? null;
            $game->songAuthor = $input['SongAuthor'] ?? null;
        }
        if (!empty($input['LevelId']) || $isQuickplay) {
            $game->difficulty = isset($input['Difficulty']) ? intval($input['Difficulty']) : null;
        }

        $game->platform = isset($input['Platform']) ? strtolower($input['Platform']) : ModPlatformId::UNKNOWN;
        $game->masterServerHost = $input['MasterServerHost'] ?? null;
        $game->masterServerPort = isset($input['MasterServerPort']) ? intval($input['MasterServerPort']) : null;

        $mpExVersion = isset($input['MpExVersion']) ? new CVersion($input['MpExVersion']) : null;
        $game->mpExVersion = $mpExVersion ? $mpExVersion->toString(3) : null;

        $game->modName = $modClientInfo->modName;
        $game->modVersion = $modClientInfo->assemblyVersion;
        $game->gameVersion = $modClientInfo->beatSaberVersion;

        $game->serverType = $serverType;
        $game->hostSecret = $hostSecret;
        $game->endpoint = $endpoint;

        // -------------------------------------------------------------------------------------------------------------
        // Validation and processing

        if (!$game->isModded) {
            // For some reason the "modded" flag doesn't always get set, so apply failsafes
            if ($game->mpExVersion != null) {
                // MpEx version provided, must be modded
                $game->isModded = true;
            } else if (!$isQuickplay && $game->levelId && CString::startsWith($game->levelId, "custom_level_")) {
                // Custom song given, must be modded
                $game->isModded = true;
            }
        }

        if (!$isQuickplay && (empty($game->serverCode) || strlen($game->serverCode) !== 5|| !ctype_alnum($game->serverCode))) {
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

        if ($isQuickplay && empty($game->hostSecret)) {
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

        if ($game->serverType === HostedGame::SERVER_TYPE_VANILLA_QUICKPLAY) {
            $difficultyName = LevelDifficulty::describe($game->difficulty);
            $game->gameName = "Official Quick Play - {$difficultyName}";
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

        if ($saveOk && $game->id) {
            // Mark any older games from same owner as "ended"
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

                    if (!$isNewGame) {
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
                    $playerRecord->latency = $latency;
                    $playerRecord->isConnected = true;
                    $playerRecord->save();

                    $playerIndexesKnown[] = $sortIndex;
                }

                if ($isNewGame) {
                    // New game, player list was just freshly created
                } else if ($isResurrectedGame) {
                    // Resurrected game, player list may contain stale items - clean all except host
                    HostedGamePlayer::query()
                        ->delete()
                        ->where('hosted_game_id = ?', $game->id)
                        ->andWhere('(is_host = 0 OR sort_index = -1)')
                        ->execute();
                } else {
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
                "result" => "ok",
                "id" => $game->id
            ]);
        }

        return new InternalServerErrorResponse("Could not write game to database");
    }
}