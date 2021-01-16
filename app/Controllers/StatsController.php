<?php

namespace app\Controllers;

use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\NotFoundResponse;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class StatsController
{
    // -----------------------------------------------------------------------------------------------------------------
    // Consts

    const CACHE_KEY_MAIN = "stats_page";
    const CACHE_TTL_MAIN = 60;

    const CACHE_KEY_TOP_LEVELS_PREFIX = "stats_top_levels_";
    const CACHE_TTL_TOP_LEVELS = 120;

    // -----------------------------------------------------------------------------------------------------------------
    // Shared functions

    private function queryTopLevels(bool $customs = true, int $offset = 0, int $pageSize = 10): array
    {
        $query = LevelRecord::query()
            ->where('stat_play_count > 0')
            ->offset($offset)
            ->limit(10)
            ->orderBy('stat_play_count DESC');

        if ($customs) {
            $query->andWhere('hash IS NOT NULL');
        } else {
            $query->andWhere('hash IS NULL');
        }

        return $query->queryAllModels();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Stats main page

    public function getStats(Request $request)
    {
        $resCache = new ResponseCache(self::CACHE_KEY_MAIN, self::CACHE_TTL_MAIN);

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

        $topLevelsCustom = $this->queryTopLevels(true, 0, 10);
        $topLevelsOfficial = $this->queryTopLevels(false, 0, 10);

        $view = new View('stats.twig');
        $view->set('pageUrl', '/stats');
        $view->set('stats', [
            'uniqueHostCount' => $uniqueHostCount,
            'uniqueLevelCount' => $uniqueLevelCount,
            'totalPlayStat' => $totalPlayStat,
            'topLevelsCustom' => $topLevelsCustom,
            'topLevelsOfficial' => $topLevelsOfficial
        ]);

        $response = $view->asResponse();
        $resCache->writeResponse($response);
        return $response;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Top 100 levels sub pages

    public function getTopLevelsSubPage(Request $request, string $urlSection)
    {
        switch ($urlSection) {
            case "custom-levels":
                $showCustomLevels = true;
                $pageTitle = "Top 100 Custom Levels";
                break;
            case "official-levels":
                $showCustomLevels = false;
                $pageTitle = "Top 100 Official Levels";
                break;
            default:
                return new NotFoundResponse();
        }

        $resCacheKey = self::CACHE_KEY_TOP_LEVELS_PREFIX . $urlSection;
        $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL_TOP_LEVELS);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        $topLevels = $this->queryTopLevels($showCustomLevels, 0, 100);

        $view = new View('stats_top_levels.twig');
        $view->set('pageUrl', "/stats/top/{$urlSection}");
        $view->set('pageTitle', $pageTitle);
        $view->set('levels', $topLevels);

        $response = $view->asResponse();
        $resCache->writeResponse($response);
        return $response;
    }
}