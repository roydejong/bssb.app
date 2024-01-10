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
use app\Models\Joins\LevelHistoryPlayerWithDetails;
use app\Models\Joins\PlayerRelationshipJoin;
use app\Models\Player;
use app\Models\PlayerAvatar;
use app\Models\ProfileStats;
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

        $baseUrl = "/player/{$player->getUrlSafeUserId()}";
        $pageUrl = $baseUrl;

        // -------------------------------------------------------------------------------------------------------------
        // Tabs

        $tabId = "info";
        $templateName = "player-profile-info";
        $titleSuffix = "Profile";
        $loadStats = true;
        $loadHistory = false;
        $loadCurrent = true;
        $loadFriends = false;

        if ($profileSection) {
            switch ($profileSection) {
                case "plays":
                    $tabId = "plays";
                    $templateName = "player-profile-plays";
                    $titleSuffix = "Play history";
                    $loadStats = false;
                    $loadHistory = true;
                    $loadCurrent = false;
                    $pageUrl = "/player/{$player->getUrlSafeUserId()}/{$tabId}";
                    break;
                case "friends":
                    $tabId = "friends";
                    $templateName = "player-profile-friends";
                    $titleSuffix = "Friends";
                    $loadStats = false;
                    $loadCurrent = false;
                    $loadFriends = true;
                    if (!$isMe) {
                        // Friends list is visible to self only
                        return new RedirectResponse('/me');
                    }
                    $pageUrl = "/player/{$player->getUrlSafeUserId()}/{$tabId}";
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
        // Player data

        $enablePrivacyShield = $player->type === PlayerType::PlayerObserved || !$player->showHistory;

        $paginationBaseUrl = $tabId !== "info" ? ($baseUrl . "/{$tabId}") : null;

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
                ->where("is_stale = 0")
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
        // Friends data

        $friendsData = null;

        if ($loadFriends) {
            $friendsData = $this->loadFriendsData($player);
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View("pages/{$templateName}.twig");
        $view->set('baseUrl', $player->getWebDetailUrl());
        $view->set('pageUrl', $pageUrl);
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
        $view->set('friendsData', $friendsData);

        $response = $view->asResponse();
        $resCache->writeResponse($response);
        return $response;
    }

    private function queryPlayerStats(Player $player): array
    {
        return ProfileStats::getOrCreateForPlayer($player->id)
            ->serialize();
    }

    private function loadFriendsData(Player $player)
    {
        return PlayerRelationshipJoin::queryFriendships($player)
            ->getPaginatedQuery()
            ->queryAllModels();
    }
}