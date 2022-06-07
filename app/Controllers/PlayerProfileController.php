<?php

namespace app\Controllers;

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\NotFoundResponse;
use app\HTTP\Responses\RedirectResponse;
use app\Models\Enums\PlayerType;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use app\Models\Joins\LevelHistoryPlayerWithDetails;
use app\Models\LevelHistoryPlayer;
use app\Models\Player;
use app\Models\PlayerAvatar;
use app\Session\Session;

class PlayerProfileController
{
    const CACHE_KEY_PREFIX = "player_detail_page_";
    const CACHE_TTL = 300;

    private const LevelHistoryPageSize = 12;

    public function getPlayerProfile(Request $request, string $userId, ?string $profileSection = null)
    {
        $userId = Player::restoreUserIdFromUrl($userId);

        $session = Session::getInstance();
        $isAuthed = $session->getIsSteamAuthed();
        $viewerPlayer = $isAuthed ? $session->getPlayer() : null;

        $pageIndex = intval($request->queryParams['page'] ?? 1) - 1;

        // -------------------------------------------------------------------------------------------------------------
        // Tabs

        $tabId = "info";
        $templateName = "player-profile-info";
        $titleSuffix = "Profile";
        $loadStats = true;
        $loadHistory = false;
        $loadCurrent = true;

        if ($profileSection) {
            switch ($profileSection) {
                case "plays":
                    $tabId = "plays";
                    $templateName = "player-profile-plays";
                    $titleSuffix = "Play history";
                    $loadStats = false;
                    $loadHistory = true;
                    $loadCurrent = false;
                    break;
                default:
                    return new NotFoundResponse();
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Cache

        $resCacheKey = self::CACHE_KEY_PREFIX . "{$tabId}_{$pageIndex}_" . md5($userId); // hash to prevent key manipulation
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

        $isMe = $viewerPlayer?->id === $player->id;
        $isDedicatedServer = $player->getIsDedicatedServer();

        $enablePrivacyShield = $player->type === PlayerType::PlayerObserved || !$player->showHistory;

        $baseUrl = $player->getWebDetailUrl();
        $paginationBaseUrl = $tabId !== "info" ? ($baseUrl . "/{$tabId}") : null;

        // -------------------------------------------------------------------------------------------------------------
        // Player data

        $stats = [];
        $currentGame = null;
        $avatarData = null;

        if ($loadStats) {
            $stats = $this->queryPlayerStats($player);

            /**
             * @var $avatarData PlayerAvatar|null
             */
            $avatarData = PlayerAvatar::query()
                ->where('player_id = ?', $player->id)
                ->querySingleModel();
        }

        if ($loadCurrent) {
            $currentGame = HostedGame::query()
                ->select("hg.*")
                ->from("hosted_games hg")
                ->innerJoin("hosted_game_players hgp ON (hgp.hosted_game_id = hg.id AND hgp.user_id = ?)",
                    $userId)
                ->where("last_update >= ?", HostedGame::getStaleGameCutoff())
                ->andWhere("ended_at IS NULL")
                ->andWhere('hgp.is_connected = 1')
                ->orderBy('hg.id DESC')
                ->querySingleModel();
        }

        $timeSinceActive = time() - $player->lastSeen->getTimestamp();
        $activeNow = $currentGame || ($timeSinceActive <= (HostedGame::STALE_GAME_AFTER_MINUTES * 60));

        // -------------------------------------------------------------------------------------------------------------
        // History data

        $levelHistory = [];
        if (!$enablePrivacyShield && $loadHistory) {
            $levelHistoryQuery = LevelHistoryPlayerWithDetails::query()
                ->select('lh.*, lhp.*, lr.*, hg.game_name, hg.first_seen, lhp.id AS id')
                ->from('level_history_players lhp')
                ->innerJoin('level_histories lh ON (lh.id = lhp.level_history_id)')
                ->innerJoin('level_records lr ON (lr.id = lh.level_record_id)')
                ->innerJoin('hosted_games hg ON (hg.id = lh.hosted_game_id)')
                ->where('lhp.player_id = ?', $player->id)
                ->andWhere('lhp.end_state != ?', PlayerLevelEndState::NotStarted->value)
                ->andWhere('lhp.end_reason NOT IN (?)', [PlayerLevelEndReason::ConnectedAfterLevelEnded->value,
                    PlayerLevelEndReason::StartupFailed->value, PlayerLevelEndReason::WasInactive->value])
                ->orderBy('lh.ended_at DESC');

            $levelHistoryPaginator = $levelHistoryQuery
                ->paginate()
                ->setPageIndex($pageIndex)
                ->setQueryPageSize(self::LevelHistoryPageSize);

            if (!$levelHistoryPaginator->getIsValidPage())
                return new RedirectResponse($paginationBaseUrl);

            $levelHistory = $levelHistoryPaginator
                ->getPaginatedQuery()
                ->queryAllModels();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View("pages/{$templateName}.twig");
        $view->set('player', $player);
        $view->set('pageTitle', "{$player->getDisplayName()}'s {$titleSuffix}");
        $view->set('pageDescr', "{$player->getDisplayName()} is a Beat Saber multiplayer {$player->describeType(true)}. View their {$titleSuffix} here.");
        $view->set('activeNow', $activeNow);
        $view->set('currentGame', $currentGame);
        $view->set('avatarData', $avatarData?->jsonSerialize());
        $view->set('privacyMode', $enablePrivacyShield);
        $view->set('stats', $stats);
        $view->set('levelHistory', $levelHistory);
        $view->set('paginator', $levelHistoryPaginator ?? null);
        $view->set('profileBaseUrl', $baseUrl);
        $view->set('paginationBaseUrl', $paginationBaseUrl);
        $view->set('isMe', $isMe);
        $view->set('isDedicatedServer', $isDedicatedServer);
        $view->set('profileTab', $tabId);
        $view->set('siteRole', $player->getSiteRole());

        $response = $view->asResponse();
        $resCache->writeResponse($response);
        return $response;
    }

    private function queryPlayerStats(Player $player): array
    {
        $stats = [];

        $stats['hostCount'] = HostedGame::query()
                ->select('COUNT(id)')
                ->where('owner_id = ? OR manager_id = ?', $player->userId, $player->userId)
                ->querySingleValue() ?? 0;

        $stats['joinCount'] = HostedGamePlayer::query()
                ->select('COUNT(id)')
                ->where('user_id = ? AND is_host = 0', $player->userId)
                ->querySingleValue() ?? 0;

        $stats['playCount'] = 0;
        $stats['totalScore'] = 0;
        $stats['goodCuts'] = 0;
        $stats['badCuts'] = 0;
        $stats['missCount'] = 0;

        $sumStats = LevelHistoryPlayer::query()
            ->select('COUNT(id) AS playCount, SUM(modified_score) AS totalScore, SUM(good_cuts) AS goodCuts, SUM(bad_cuts) AS badCuts, SUM(miss_count) AS missCount')
            ->where('player_id = ?', $player->id)
            ->andWhere('end_state != ?', PlayerLevelEndState::NotStarted->value)
            ->limit(1)
            ->querySingleRow();
        foreach ($sumStats as $key => $value)
            $stats[$key] = intval($value ?? 0);

        $maxHitCount = $stats['goodCuts'] + $stats['badCuts'] + $stats['missCount'];
        $stats['hitCountPercentage'] = $maxHitCount > 0 ? ($stats['goodCuts'] / $maxHitCount) : 0;

        return $stats;
    }
}