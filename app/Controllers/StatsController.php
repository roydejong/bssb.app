<?php

namespace app\Controllers;

use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class StatsController
{
    const CACHE_KEY = "stats_page";
    const CACHE_TTL = 60;

    public function getStats(Request $request)
    {
        $resCache = new ResponseCache(self::CACHE_KEY, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        $uniqueHostCount = intval(HostedGame::query()
            ->select("COUNT(DISTINCT(owner_id)) AS count")
            ->querySingleValue());

        $uniqueLevelCount = intval(LevelRecord::query()
            ->select("COUNT(DISTINCT(id)) AS count")
            ->querySingleValue());

        $totalPlayStat = intval(LevelRecord::query()
            ->select("SUM(stat_play_count) AS count")
            ->querySingleValue());

        $view = new View('stats.twig');
        $view->set('stats', [
            'uniqueHostCount' => $uniqueHostCount,
            'uniqueLevelCount' => $uniqueLevelCount,
            'totalPlayStat' => $totalPlayStat
        ]);

        $response = $view->asResponse();
        $resCache->writeResponse($response);
        return $response;
    }
}