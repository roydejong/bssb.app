<?php

namespace app\Controllers;

use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\Models\Enums\PlayerType;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use app\Models\Player;
use app\Models\PlayerAvatar;
use app\Session\Session;

class PlayerProfileController
{
    const CACHE_KEY_PREFIX = "player_detail_page_";
    const CACHE_TTL = 60;

    public function getPlayerProfile(Request $request, string $userId)
    {
        $userId = Player::restoreUserIdFromUrl($userId);

        $session = Session::getInstance();
        $isAuthed = $session->getIsSteamAuthed();
        $viewerPlayer = $isAuthed ? $session->getPlayer() : null;

        // -------------------------------------------------------------------------------------------------------------
        // Cache

        $resCache = null;

        if (!$isAuthed) {
            $resCacheKey = self::CACHE_KEY_PREFIX . md5($userId); // hash to prevent key manipulation
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

        if (!$enablePrivacyShield) {
            // TODO Query game history
        }

        $stats = [];
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

        $timeSinceActive = time() - $player->lastSeen->getTimestamp();
        $activeNow = ($timeSinceActive <= (HostedGame::STALE_GAME_AFTER_MINUTES * 60));

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('pages/player-profile.twig');
        $view->set('player', $player);
        $view->set('pageTitle', "{$player->getDisplayName()}'s Profile");
        $view->set('pageDescr', "{$player->getDisplayName()} is a Beat Saber multiplayer {$player->describeType(true)}. View their profile and played games here.");
        $view->set('activeNow', $activeNow);
        $view->set('avatarData', $avatarData?->jsonSerialize());
        $view->set('privacyMode', $enablePrivacyShield);
        $view->set('stats', $stats);
        $view->set('isMe', $isMe);

        $response = $view->asResponse();

        if ($resCache) {
            @$resCache->writeResponse($response);
        }

        return $response;
    }
}