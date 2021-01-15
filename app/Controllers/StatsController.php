<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class StatsController
{
    public function getStats(Request $request)
    {
        $uniqueHostCount = intval(HostedGame::query()
            ->select("COUNT(DISTINCT(owner_id)) AS count")
            ->querySingleValue());

        $uniqueLevelCount = intval(LevelRecord::query()
            ->select("COUNT(DISTINCT(id)) AS count")
            ->querySingleValue());

        $view = new View('stats.twig');
        $view->set('stats', [
            'uniqueHostCount' => $uniqueHostCount,
            'uniqueLevelCount' => $uniqueLevelCount
        ]);
        return $view->asResponse();
    }
}