<?php

namespace app\Controllers\API;

use app\BeatSaber\LevelId;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CString;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\InternalServerErrorResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class AnnounceController
{
    public function announce(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Pre-flight checks

        if (!$request->getIsValidModClientRequest()
            || !$request->getIsJsonRequest()
            || $request->method !== "POST") {
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Read input

        $input = $request->getJson();
        $ownerId = $input['OwnerId'] ?? "";

        if (empty($ownerId) || $ownerId === "SERVER_MESSAGE") {
            // Owner ID is always required, and can't be "SERVER_MESSAGE" as we reserve that for our own use
            return new BadRequestResponse();
        }

        /**
         * @var $gameByOwner HostedGame|null
         */
        $gameByOwner = HostedGame::query()
            ->where('owner_id = ?', $ownerId)
            ->querySingleModel();

        $lastLobbyState = null;

        if ($gameByOwner) {
            // Replace existing
            $game = $gameByOwner;
            $lastLobbyState = $game->lobbyState;
        } else {
            // Create new
            $game = new HostedGame();
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
            $game->difficulty = isset($input['Difficulty']) ? intval($input['Difficulty']) : null;
        }

        $game->platform = isset($input['Platform']) ? strtolower($input['Platform']) : ModPlatformId::UNKNOWN;
        $game->masterServerHost = $input['MasterServerHost'] ?? null;
        $game->masterServerPort = isset($input['MasterServerPort']) ? intval($input['MasterServerPort']) : null;

        // -------------------------------------------------------------------------------------------------------------
        // Validation and processing

        if ($game->levelId && CString::startsWith($game->levelId, "custom_level_")) {
            // Custom song: this game should be detected as modded
            // (For some reason the "modded" flag doesn't always get set, possibly due to MpEx changes)
            $game->isModded = true;
        }

        if (empty($game->serverCode) || strlen($game->serverCode) !== 5 || !ctype_alnum($game->serverCode)) {
            // Server code should always be alphanumeric, 5 characters, e.g. "ABC123"
            return new BadRequestResponse();
        }

        $maxPlayerLimit = $game->isModded ? HostedGame::MAX_PLAYER_LIMIT_MODDED : HostedGame::MAX_PLAYER_LIMIT_VANILLA;
        if ($game->playerLimit <= 0 || $game->playerLimit > $maxPlayerLimit) {
            $game->playerLimit = $maxPlayerLimit;
        }

        if ($game->playerCount <= 0) {
            $game->playerCount = 1;
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

        // -------------------------------------------------------------------------------------------------------------
        // Level data sync

        if ($game->levelId && $game->songName) {
            // Update base data
            $levelRecord = LevelRecord::syncFromAnnounce($game->levelId, $game->songName, $game->songAuthor);

            // Update play count stat if we transitioned from lobby to the level
            $gameWasInLobby = ($lastLobbyState === null || $lastLobbyState === MultiplayerLobbyState::LobbySetup);
            $gameIsRunningOrStarting = ($game->lobbyState === MultiplayerLobbyState::GameRunning
                || $game->lobbyState === MultiplayerLobbyState::GameStarting);

            if ($gameWasInLobby && $gameIsRunningOrStarting) {
                $levelRecord->incrementPlayStat();
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Write

        // Update timestamps
        $now = new \DateTime('now');

        if (empty($game->firstSeen)) {
            $game->firstSeen = $now;
        }

        $game->lastUpdate = $now;
        $game->endedAt = null;

        // Discard boring games
        if ($game->getIsUninteresting()) {
            $game->endedAt = new \DateTime('now');
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