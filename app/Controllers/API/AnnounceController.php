<?php

namespace app\Controllers\API;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\InternalServerErrorResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;

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

        $game = new HostedGame();
        $game->serverCode = strtoupper($input['ServerCode'] ?? "");
        $game->gameName = $input['GameName'] ?? "";
        $game->ownerId = $input['OwnerId'] ?? "";
        $game->ownerName = $input['OwnerName'] ?? "";
        $game->playerCount = intval($input['PlayerCount'] ?? 0);
        $game->playerLimit = intval($input['PlayerLimit'] ?? 0);
        $game->lobbyState = intval($input['LobbyState']);
        $game->levelId = $input['LevelId'] ?? null;
        $game->songName = $input['SongName'] ?? null;
        $game->songAuthor = $input['SongAuthor'] ?? null;
        $game->difficulty = intval($input['Difficulty']);
        $game->platform = isset($input['Platform']) ? strtolower($input['Platform']) : "";
        $game->masterServerHost = $input['MasterServerHost'] ?? null;
        $game->masterServerPort = $input['MasterServerPort'] ?? null;

        // -------------------------------------------------------------------------------------------------------------
        // Replace existing game record

        /**
         * @var $gameByOwner HostedGame|null
         */
        $gameByOwner = HostedGame::query()
            ->where('owner_id = ?', $game->ownerId)
            ->querySingleModel();

        if ($gameByOwner && $gameByOwner->id !== $game->id) {
            $game->id = $gameByOwner->id; // replace existing game by taking over its id
        }

        // -------------------------------------------------------------------------------------------------------------
        // Save & respond

        $now = new \DateTime('now');

        if (empty($game->firstSeen)) {
            $game->firstSeen = $now;
        }

        $game->lastUpdate = $now;

        if ($game->save()) {
            return new JsonResponse([
                "result" => "ok",
                "id" => $game->id
            ]);
        }

        return new InternalServerErrorResponse("Could not write game to database");
    }
}