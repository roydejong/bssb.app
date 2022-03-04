<?php

namespace app\Controllers;

use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\NotFoundResponse;
use app\Models\Enums\PlayerType;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use app\Models\Joins\LevelHistoryPlayerWithDetails;
use app\Models\Player;
use app\Models\PlayerAvatar;
use app\Session\Session;

class PlayerProfileController
{
    const CACHE_KEY_PREFIX = "player_detail_page_";
    const CACHE_TTL = 300;

    public function getPlayerProfile(Request $request, string $userId, ?string $profileSection = null)
    {
        $userId = Player::restoreUserIdFromUrl($userId);

        $session = Session::getInstance();
        $isAuthed = $session->getIsSteamAuthed();
        $viewerPlayer = $isAuthed ? $session->getPlayer() : null;

        // -------------------------------------------------------------------------------------------------------------
        // Tabs

        $tabId = "info";
        $templateName = "player-profile-info";
        $titleSuffix = "Profile";
        $loadStats = true;
        $loadHistory = false;

        if ($profileSection) {
            switch ($profileSection) {
                case "plays":
                    $tabId = "plays";
                    $templateName = "player-profile-plays";
                    $titleSuffix = "Play history";
                    $loadStats = false;
                    $loadHistory = true;
                    break;
                default:
                    return new NotFoundResponse();
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Cache

        $resCache = null;

        if (!$isAuthed) {
            $resCacheKey = self::CACHE_KEY_PREFIX . $tabId . "_" . md5($userId); // hash to prevent key manipulation
            $resCache = new ResponseCache($resCacheKey, self::CACHE_TTL);

            if ($resCache->getIsAvailable()) {
                return $resCache->readAsResponse();
            }
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

        $isMe = $viewerPlayer->id === $player->id;

        $enablePrivacyShield = $player->type === PlayerType::PlayerObserved || !$player->showHistory;

        // -------------------------------------------------------------------------------------------------------------
        // Player data

        $stats = [];
        $avatarData = null;

        if ($loadStats) {
            $stats['hostCount'] = HostedGame::query()
                ->select('COUNT(id)')
                ->where('owner_id = ? OR manager_id = ?', $player->userId, $player->userId)
                ->querySingleValue();
            $stats['joinCount'] = HostedGamePlayer::query()
                ->select('COUNT(id)')
                ->where('user_id = ? AND is_host = 0', $player->userId)
                ->querySingleValue();
            $stats['playCount'] = 0; // TODO LevelHistoryPlayer count

            /**
             * @var $avatarData PlayerAvatar|null
             */
            $avatarData = PlayerAvatar::query()
                ->where('player_id = ?', $player->id)
                ->querySingleModel();
        }

        $timeSinceActive = time() - $player->lastSeen->getTimestamp();
        $activeNow = ($timeSinceActive <= (HostedGame::STALE_GAME_AFTER_MINUTES * 60));

        // -------------------------------------------------------------------------------------------------------------
        // History data

        $levelHistory = [];
        if (!$enablePrivacyShield && $loadHistory) {
            $pageIndex = 0;
            $pageSize = 12;

            $levelHistory = LevelHistoryPlayerWithDetails::queryPlayerHistory($player->id, $pageIndex, $pageSize);
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View("pages/{$templateName}.twig");
        $view->set('player', $player);
        $view->set('pageTitle', "{$player->getDisplayName()}'s {$titleSuffix}");
        $view->set('pageDescr', "{$player->getDisplayName()} is a Beat Saber multiplayer {$player->describeType(true)}. View their {$titleSuffix} here.");
        $view->set('activeNow', $activeNow);
        $view->set('avatarData', $avatarData?->jsonSerialize());
        $view->set('privacyMode', $enablePrivacyShield);
        $view->set('stats', $stats);
        $view->set('levelHistory', $levelHistory);
        $view->set('isMe', $isMe);
        $view->set('profileBaseUrl', $player->getWebDetailUrl());
        $view->set('profileTab', $tabId);

        $response = $view->asResponse();

        if ($resCache) {
            @$resCache->writeResponse($response);
        }

        return $response;
    }
}