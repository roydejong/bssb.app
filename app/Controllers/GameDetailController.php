<?php

namespace app\Controllers;

use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\RedirectResponse;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class GameDetailController
{
    const CACHE_KEY_PREFIX = "game_detail_page_";
    const CACHE_TTL = 10;

    public function getGameDetail(Request $request, string $hashId)
    {
        // -------------------------------------------------------------------------------------------------------------
        // Cache

        $id = HostedGame::hash2id($hashId);

        if (!$id) {
            // Not a valid hash id
            return new BadRequestResponse();
        }

        $resCacheKey = self::CACHE_KEY_PREFIX . $id;
        $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Game data

        $game = HostedGame::fetch($id);

        if (!$game) {
            // Not found, 404 redirect
            return new RedirectResponse('/', 404);
        }

        // -------------------------------------------------------------------------------------------------------------
        // Level data

        $level = null;

        if ($game->levelId) {
            /**
             * @var $level LevelRecord
             */
            $level = LevelRecord::query()
                ->where('level_id = ?', $game->levelId)
                ->querySingleModel();
        }

        // -------------------------------------------------------------------------------------------------------------
        // GeoIP info

        $geoIp = new GeoIp();

        $geoCountry = null;
        $geoText = null;

        if ($game->endpoint) {
            $geoCountry = $geoIp->getCountryCode($game->endpoint);
            $geoText = $geoIp->describeLocation($game->endpoint);
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('pages/game-detail-info.twig');
        $view->set('pageUrl', $game->getWebDetailUrl());
        $view->set('game', $game);
        $view->set('players', $game->fetchPlayers());
        $view->set('level', $level);
        $view->set('ldJson', $this->generateLdJson($game, $level));
        $view->set('geoCountry', $geoCountry);
        $view->set('geoText', $geoText);

        $response = $view->asResponse();
        @$resCache->writeResponse($response);
        return $response;
    }

    private function generateLdJson(HostedGame $game, ?LevelRecord $level): array
    {
        $serverStatus = "Online";
        if ($game->getIsStale() || $game->endedAt) {
            $serverStatus = "OfflinePermanently";
        } else if ($game->playerCount >= $game->playerLimit) {
            $serverStatus = "OnlineFull";
        }

        return [
            '@context' => 'http://schema.org/',
            '@type' => 'GameServer',
            'game' => [
                '@type' => 'VideoGame',
                'name' => 'Beat Saber Multiplayer',
                'playMode' => 'MultiPlayer',
                'gamePlatform' => ucfirst($game->platform),
                'numberOfPlayers' => $game->playerLimit,
                'applicationCategory' => 'Game, Multimedia',
                'applicationSubCategory' => 'VR Game',
                'operatingSystem' => 'Windows, Oculus Quest'
            ],
            'playersOnline' => $game->playerCount,
            'serverStatus' => $serverStatus,
            'name' => $game->gameName,
            'identifier' => $game->serverCode,
            'image' => $level?->coverUrl ?? null
        ];
    }
}