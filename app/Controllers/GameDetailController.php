<?php

namespace app\Controllers;

use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\RedirectResponse;
use app\Models\HostedGame;
use app\Models\Joins\HostedGamePlayerWithPlayerDetails;
use app\Models\Joins\LevelHistoryPlayerWithDetails;
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

        $level = $game->fetchLevel();

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
        $view->set('baseUrl', $game->getWebDetailUrl());
        $view->set('game', $game);
        $view->set('level', $level);
        $view->set('ldJson', $this->generateLdJson($game, $level));
        $view->set('geoCountry', $geoCountry);
        $view->set('geoText', $geoText);

        $response = $view->asResponse();
        @$resCache->writeResponse($response);
        return $response;
    }

    public function getGameDetailPlayers(Request $request, string $hashId)
    {
        // -------------------------------------------------------------------------------------------------------------
        // Input

        $id = HostedGame::hash2id($hashId);

        if (!$id) {
            // Not a valid hash id
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Game data

        $game = HostedGame::fetch($id);

        if (!$game) {
            // Not found, 404 redirect
            return new RedirectResponse('/', 404);
        }

        $level = $game->fetchLevel();
        $players = HostedGamePlayerWithPlayerDetails::queryAllForHostedGame($game->id);

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('pages/game-detail-players.twig');
        $view->set('baseUrl', $game->getWebDetailUrl());
        $view->set('game', $game);
        $view->set('level', $level);
        $view->set('players', $players);
        return $view->asResponse();
    }

    public function getGameDetailPlays(Request $request, string $hashId)
    {
        // -------------------------------------------------------------------------------------------------------------
        // Input

        $id = HostedGame::hash2id($hashId);

        if (!$id) {
            // Not a valid hash id
            return new BadRequestResponse();
        }

        $pageIndex = intval($request->queryParams['page'] ?? 1) - 1;

        // -------------------------------------------------------------------------------------------------------------
        // Game data

        $game = HostedGame::fetch($id);

        if (!$game) {
            // Not found, 404 redirect
            return new RedirectResponse('/', 404);
        }

        $level = $game->fetchLevel();

        $baseUrl = $game->getWebDetailUrl();
        $paginationBaseUrl = $baseUrl . "/plays";

        // -------------------------------------------------------------------------------------------------------------
        // Level history query

        $levelHistoryQuery = LevelHistoryPlayerWithDetails::query()
            ->select('lh.*, lr.*, hg.game_name, hg.first_seen, lh.id AS id')
            ->from('level_histories lh')
            ->innerJoin('level_records lr ON (lr.id = lh.level_record_id)')
            ->innerJoin('hosted_games hg ON (hg.id = lh.hosted_game_id)')
            ->where('lh.hosted_game_id = ?', $id)
            ->orderBy('lh.ended_at IS NULL DESC, lh.ended_at DESC');

        $levelHistoryPaginator = $levelHistoryQuery
            ->paginate()
            ->setPageIndex($pageIndex)
            ->setQueryPageSize(self::LevelHistoryPageSize);

        $levelHistory = $levelHistoryPaginator
            ->getPaginatedQuery()
            ->queryAllModels();

        $isNowPlaying = false;
        foreach ($levelHistory as $item) {
            if (!$item->endedAt) {
                $isNowPlaying = true;
                break;
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('pages/game-detail-plays.twig');
        $view->set('baseUrl', $baseUrl);
        $view->set('game', $game);
        $view->set('level', $level);
        $view->set('levelHistory', $levelHistory);
        $view->set('isNowPlaying', $isNowPlaying);
        $view->set('paginator', $levelHistoryPaginator);
        $view->set('paginationBaseUrl', $paginationBaseUrl);
        return $view->asResponse();
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

    private const LevelHistoryPageSize = 12;
}