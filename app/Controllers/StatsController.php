<?php

namespace app\Controllers;

use app\BeatSaber\Bplist;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\NotFoundResponse;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class StatsController
{
    // -----------------------------------------------------------------------------------------------------------------
    // Shared functions

    private function queryTopLevels(bool $customs = true, int $offset = 0, int $pageSize = 10): array
    {
        $query = LevelRecord::query()
            ->where('stat_play_count > 0')
            ->offset($offset)
            ->limit($pageSize)
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

    const CACHE_KEY_MAIN = "stats_page";
    const CACHE_TTL_MAIN = 60;

    public function getStats(Request $request)
    {
        $resCache = new ResponseCache(self::CACHE_KEY_MAIN, self::CACHE_TTL_MAIN);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        $uniqueHostCount = intval(HostedGame::query()
            ->select("COUNT(DISTINCT(owner_id)) AS count")
            ->where('server_type IS NULL OR server_type = ?', HostedGame::SERVER_TYPE_PLAYER_HOST)
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

    const CACHE_KEY_TOP_LEVELS_PREFIX = "stats_top_levels_";
    const CACHE_TTL_TOP_LEVELS = 120;

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
        $view->set('urlSection', $urlSection);
        $view->set('levels', $topLevels);

        $response = $view->asResponse();
        $resCache->writeResponse($response);
        return $response;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Top 100 levels: playlist download

    const CACHE_KEY_TOP_LEVELS_PLAYLIST_PREFIX = "stats_top_levels_playlist_";
    const CACHE_TTL_TOP_LEVELS_PLAYLIST = 600;

    public function getTopLevelsPlaylist(Request $request, string $urlSection)
    {
        if ($urlSection !== "custom-levels") {
            // Only supported for custom levels because official ones don't have a hash and probably won't work
            return new NotFoundResponse();
        }

        $bpListRaw = null;

        $now = new \DateTime('now');

        $resCacheKey = self::CACHE_KEY_TOP_LEVELS_PLAYLIST_PREFIX . $urlSection;
        $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL_TOP_LEVELS_PLAYLIST);

        if ($resCache->getIsAvailable()) {
            // Cache hit
            $bplRaw = $resCache->read();
        } else {
            // No cached version available, generate new bplist now
            $nowDateText = $now->format('Y-m-d');
            $nowDateTimeText = $now->format('c');

            $bplist = new Bplist();
            $bplist->setTitle("BSSB 100 Most Played Custom Levels {$nowDateText}");
            $bplist->setAuthor("bssb.app");
            $bplist->setDescription("Top 100 Custom Levels based on play count seen by the server browser (generated {$nowDateTimeText})");
            $bplist->setImageFromLocalFile(DIR_BASE . "/public/static/bsassets/BSSBTop100CustomLevels256.png");
            $bplist->setSyncUrl("https://bssb.app/stats/top/custom-levels/playlist");

            $topLevels = $this->queryTopLevels(true, 0, 100);
            foreach ($topLevels as $levelRecord) {
                $bplist->addSongByLevelRecord($levelRecord);
            }

            $bplRaw = $bplist->toJson();
            $resCache->write($bplRaw);
        }

        // Send response
        $nowFilenameText = $now->format('Ymd');
        $fileName = "BSSB_Top100CustomLevels_{$nowFilenameText}.bplist";

        $res = new Response(200, $bplRaw, "application/octet-stream");
        $res->headers['content-disposition'] = "attachment; filename={$fileName}";
        return $res;
    }
}