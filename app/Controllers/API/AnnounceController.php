<?php

namespace app\Controllers\API;

use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
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

        if ($gameByOwner) {
            // Replace existing
            $game = $gameByOwner;
        } else {
            // Create new
            $game = new HostedGame();
        }

        $game->serverCode = strtoupper($input['ServerCode'] ?? "");
        $game->gameName = $input['GameName'] ?? "";
        $game->ownerId = $ownerId;
        $game->ownerName = $input['OwnerName'] ?? "";
        $game->playerCount = intval($input['PlayerCount'] ?? 0);
        $game->playerLimit = intval($input['PlayerLimit'] ?? 0);
        $game->isModded = intval($input['IsModded'] ?? 0) === 1;
        $game->lobbyState = intval($input['LobbyState'] ?? MultiplayerLobbyState::None);
        $game->levelId = $input['LevelId'] ?? null;
        $game->songName = $input['SongName'] ?? null;
        $game->songAuthor = $input['SongAuthor'] ?? null;
        $game->difficulty = isset($input['Difficulty']) ? intval($input['Difficulty']) : null;
        $game->platform = isset($input['Platform']) ? strtolower($input['Platform']) : "";
        $game->masterServerHost = $input['MasterServerHost'] ?? null;
        $game->masterServerPort = isset($input['MasterServerPort']) ? intval($input['MasterServerPort']) : null;

        // -------------------------------------------------------------------------------------------------------------
        // Validation and processing

        if ($game->playerLimit <= 0 || $game->playerLimit > 5) {
            $game->playerLimit = 5;
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
            LevelRecord::syncFromAnnounce($game->levelId, $game->songName, $game->songAuthor);
        }

        // -------------------------------------------------------------------------------------------------------------
        // Write

        // Update timestamps
        $now = new \DateTime('now');

        if (empty($game->firstSeen)) {
            $game->firstSeen = $now;
        }

        $game->lastUpdate = $now;

        // Insert or update
        $saveOk = $game->save();

        // Delete any other games from same owner (conflict prevention)
        HostedGame::query()
            ->delete()
            ->where('owner_id = ? AND id < ?', $game->ownerId, $game->id)
            ->execute();

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