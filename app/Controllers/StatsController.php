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
use app\Models\Player;

class StatsController
{
    // -----------------------------------------------------------------------------------------------------------------
    // Shared functions

    const TopTrendingLevels = "trending-levels";
    const TopOfficialLevels = "official-levels";
    const TopCustomLevels = "custom-levels";
    const TopNonBeatSaverLevels = "non-beatsaver-levels";

    private function queryTopLevels(string $topType, int $offset = 0, int $pageSize = 10): array
    {
        $query = LevelRecord::query()
            ->where('stat_play_count > 0')
            ->offset($offset)
            ->limit($pageSize)
            ->orderBy('stat_play_count DESC');

        if ($topType === self::TopOfficialLevels)
            $query->andWhere('hash IS NULL AND level_id NOT LIKE ?', "custom_level_%");
        else if ($topType === self::TopTrendingLevels || $topType === self::TopCustomLevels || $topType === self::TopNonBeatSaverLevels)
            $query->andWhere('hash IS NOT NULL');

        if ($topType === self::TopNonBeatSaverLevels)
            $query->andWhere('beatsaver_id IS NULL');

        if ($topType === self::TopTrendingLevels)
            $query->orderBy('trend_factor DESC');

        return $query->queryAllModels();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Stats main page

    const CACHE_KEY_MAIN = "stats_page";
    const CACHE_TTL_MAIN = 90;

    public function getStats(Request $request)
    {
        $resCache = new ResponseCache(self::CACHE_KEY_MAIN, self::CACHE_TTL_MAIN);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        $totalPlayerCount = intval(Player::query()
            ->select("COUNT(id) AS count")
            ->querySingleValue());

        $totalLobbyCount = intval(HostedGame::query()
            ->select("COUNT(id) AS count")
            ->querySingleValue());

        $totalPlayCount = intval(LevelRecord::query()
            ->select("SUM(stat_play_count) AS count")
            ->querySingleValue());

        $topLevelsTrending = $this->queryTopLevels(self::TopTrendingLevels, 0, 10);

        $view = new View('pages/stats-overview.twig');
        $view->set('pageUrl', '/stats');
        $view->set('stats', [
            'totalPlayerCount' => $totalPlayerCount,
            'totalLobbyCount' => $totalLobbyCount,
            'totalPlayCount' => $totalPlayCount,
            'topLevelsTrending' => $topLevelsTrending
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
        $isTrending = false;

        switch ($urlSection) {
            case self::TopTrendingLevels:
                $pageTitle = "Top 100 Trending Levels";
                $pageDescr = "These are the custom levels getting the most plays in modded Beat Saber multiplayer right now.";
                $isTrending = true;
                break;
            case self::TopCustomLevels:
                $pageTitle = "Top 100 Custom Levels";
                $pageDescr = "These are the most played custom levels in modded Beat Saber multiplayer. Check out the list, or download them all at once as a playlist!";
                break;
            case self::TopOfficialLevels:
                $pageTitle = "Top 100 Official Levels";
                $pageDescr = "These are the most played official OST and DLC levels in Beat Saber multiplayer.";
                break;
            case self::TopNonBeatSaverLevels:
                $pageTitle = "Top 100 Non-Beat Saver Levels";
                $pageDescr = "These are the most played custom levels in modded Beat Saber multiplayer that aren't available on Beat Saver.";
                break;
            default:
                return new NotFoundResponse();
        }

        $resCacheKey = self::CACHE_KEY_TOP_LEVELS_PREFIX . $urlSection;
        $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL_TOP_LEVELS);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        $topLevels = $this->queryTopLevels($urlSection, 0, 100);

        $view = new View('pages/stats-top-levels.twig');
        $view->set('pageUrl', "/stats/top/{$urlSection}");
        $view->set('pageTitle', $pageTitle);
        $view->set('pageDescr', $pageDescr);
        $view->set('urlSection', $urlSection);
        $view->set('levels', $topLevels);
        $view->set('isTrending', $isTrending);

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
        $resCacheKey = self::CACHE_KEY_TOP_LEVELS_PLAYLIST_PREFIX . $urlSection;
        $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL_TOP_LEVELS_PLAYLIST, allowAuthedUsers: true);

        $now = new \DateTime('now');
        $nowDateText = $now->format('Y-m-d');

        $fileName = "bssb-top-{$urlSection}-{$nowDateText}.bplist";

        if ($resCache->getIsAvailable()) {
            // Serve from response cache
            return self::sendPlaylistResponse($fileName, $resCache->read());
        }

        // No cached version available, generate new bplist now

        switch ($urlSection) {
            case self::TopTrendingLevels:
                $playlistTitle = "BSSB Top 100 Trending Levels {$nowDateText}";
                $playlistDescription = "Top 100 Trending Levels based on play count seen by the server browser (generated {$nowDateText})";
                $playlistImage = DIR_BASE . "/public/static/bsassets/BSSBTop100TrendingLevels256.png";
                break;
            case self::TopCustomLevels:
                $playlistTitle = "BSSB 100 Most Played Custom Levels {$nowDateText}";
                $playlistDescription = "Top 100 Custom Levels based on play count seen by the server browser (generated {$nowDateText})";
                $playlistImage = DIR_BASE . "/public/static/bsassets/BSSBTop100CustomLevels256.png";
                break;
            default:
                // Only supported for specific top lists
                return new NotFoundResponse();
        }

        $bplist = new Bplist();
        $bplist->setTitle($playlistTitle);
        $bplist->setDescription($playlistDescription);
        if ($playlistImage)
            $bplist->setImageFromLocalFile($playlistImage);
        $bplist->setAuthor("bssb.app");
        $bplist->setSyncUrl("https://bssb.app/stats/top/{$urlSection}/playlist");

        $levelResults = $this->queryTopLevels($urlSection, 0, 100);
        foreach ($levelResults as $levelRecord) {
            $bplist->addSongByLevelRecord($levelRecord);
        }

        $bplRaw = $bplist->toJson();
        $resCache->write($bplRaw);
        return self::sendPlaylistResponse($fileName, $bplRaw);
    }

    private static function sendPlaylistResponse(string $fileName, string $contents): Response
    {
        $res = new Response(200, $contents, "application/octet-stream");
        $res->headers['content-disposition'] = "attachment; filename={$fileName}";
        return $res;
    }
}