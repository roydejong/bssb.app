<?php

namespace app\Controllers\API\V2;

use app\BeatSaber\GameVersionAliases;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;
use app\Models\Joins\HostedGameLevelRecord;

class BrowseController
{
    public function browse(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Preflight

        // Must be valid mod client POST request
        if (!$request->getIsValidClientRequest() || $request->method !== "POST")
            return new BadRequestResponse();

        $mci = $request->getModClientInfo();

        // -------------------------------------------------------------------------------------------------------------
        // Prep

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

        // -------------------------------------------------------------------------------------------------------------
        // Query

        // Data
        $gamesSz = [];

        /**
         * @var $games HostedGame[]
         */
        $games = $baseQuery
            ->queryAllModels();

        foreach ($games as $game) {
            $gamesSz[] = $game->jsonSerialize(false, true, false);
        }

        return new JsonResponse([
            "lobbies" => $gamesSz
        ]);
    }
}