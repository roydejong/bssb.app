<?php

namespace app\Controllers;

use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\Models\HostedGame;
use app\Models\Joins\HostedGameLevelRecord;
use app\Models\Player;
use app\Models\PlayerAvatar;

class PlayerDetailController
{
    const CACHE_KEY_PREFIX = "player_detail_page_";
    const CACHE_TTL = 60;

    public function getPlayerDetail(Request $request, string $userId)
    {
        $userId = Player::restoreUserIdFromUrl($userId);

        // -------------------------------------------------------------------------------------------------------------
        // Cache

        $resCacheKey = self::CACHE_KEY_PREFIX . md5($userId); // hash to prevent key manipulation
        $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Player lookup

        /**
         * @var $player Player|null
         */
        $player = Player::query()
            ->where('user_id = ?', $userId)
            ->querySingleModel();

        if (!$player) {
            // Not found, 404 redirect
            $view = new View('generic_error.twig');
            $view->set('pageTitle', "Player not found");
            $view->set('message', "Player not found: invalid ID, or player has never been seen by the Server Browser.");
            return $view->asResponse(404);
        }

        // -------------------------------------------------------------------------------------------------------------
        // Player data

        $baseQuery = HostedGameLevelRecord::query()
            ->select("hg.*, lr.beatsaver_id, lr.cover_url, lr.name AS level_name")
            ->from("hosted_games hg")
            ->innerJoin("hosted_game_players hgp ON (hgp.hosted_game_id = hg.id AND hgp.user_id = ?)", $player->userId)
            ->leftJoin("level_records lr ON (lr.level_id = hg.level_id)")
            ->orderBy("last_update DESC");

        $staleGameCutoff = HostedGame::getStaleGameCutoff();

        $activeGames = (clone $baseQuery)
            ->andWhere("hgp.is_connected = 1")
            ->andWhere("last_update >= ? AND ended_at IS NULL", $staleGameCutoff)
            ->queryAllModels();

        $recentGames = (clone $baseQuery)
            ->andWhere("hgp.is_connected = 0 OR last_update < ? OR ended_at IS NOT NULL", $staleGameCutoff)
            ->limit(10)
            ->queryAllModels();

        /**
         * @var $avatarData PlayerAvatar|null
         */
        $avatarData = PlayerAvatar::query()
            ->where('player_id = ?', $player->id)
            ->querySingleModel();

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('player_detail.twig');
        $view->set('player', $player);
        $view->set('activeGames', $activeGames);
        $view->set('recentGames', $recentGames);
        $view->set('pageTitle', "Player: {$player->getDisplayName()}");
        $view->set('pageDescr', "{$player->getDisplayName()} is a Beat Saber multiplayer {$player->describeType(true)}. View their profile and played games here.");
        $view->set('activeNow', !empty($activeGames));
        $view->set('avatarData', $avatarData?->jsonSerialize());

        $response = $view->asResponse();
        @$resCache->writeResponse($response);
        return $response;
    }
}