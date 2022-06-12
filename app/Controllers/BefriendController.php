<?php

namespace app\Controllers;

use app\BeatSaber\Enums\PlayerLevelEndState;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\RedirectResponse;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use app\Models\Joins\PlayerRelationshipJoin;
use app\Models\LevelHistoryPlayer;
use app\Models\Player;
use app\Models\PlayerFriend;
use app\Session\Session;

class BefriendController
{
    public function getBefriend(Request $request)
    {
        // -------------------------------------------------------------------------------------------------------------
        // Session

        $session = Session::getInstance();

        $isAuthed = $session->getIsSteamAuthed();
        $selfPlayer = $isAuthed ? $session->getPlayer() : null;

        if (!$isAuthed || !$selfPlayer)
            return new RedirectResponse('/login');

        // -------------------------------------------------------------------------------------------------------------
        // Form/lookup

        $isSubmission = false;
        $queryValue = null;
        $paginatedSearch = null;
        $results = null;
        $resultMessage = null;

        $pageIndex = intval($request->queryParams['page'] ?? 1) - 1;
        $queryValue = $request->queryParams['query'] ?? null;

        $contextSource = $request->queryParams['source'] ?? null;
        $contextIsFriendsList = $contextSource === "friends_list";

        if ($queryValue && !$contextIsFriendsList) {
            $queryValue = str_replace('%', '', $queryValue);
            $queryValue = trim($queryValue);

            if (strlen($queryValue) >= 1) {
                $paginatedSearch = PlayerRelationshipJoin::querySearch($selfPlayer, $queryValue);
                $paginatedSearch->setPageIndex($pageIndex);
                $paginatedSearch->setQueryPageSize(12);

                /**
                 * @var $results PlayerRelationshipJoin[]
                 */
                $results = $paginatedSearch
                    ->getPaginatedQuery()
                    ->queryAllModels();
            }
        }

        if ($request->method === "POST") {
            $isSubmission = true;

            if (isset($request->postParams['action-accept'])) {
                // Accept incoming request
                $friendshipId = intval($request->postParams['action-accept']);

                if ($friendshipId) {
                    $friendship = PlayerFriend::fetch($friendshipId);

                    if ($friendship && $friendship->tryAccept($selfPlayer)) {
                        $resultMessage = "Accepted friend request";
                    }
                }
            } else if (isset($request->postParams['action-remove'])) {
                // Remove friend or cancel request
                $friendshipId = intval($request->postParams['action-remove']);

                if ($friendshipId) {
                    $friendship = PlayerFriend::fetch($friendshipId);

                    if ($friendship && $friendship->tryDelete($selfPlayer)) {
                        if ($friendship->isPending) {
                            $resultMessage = "Removed request";
                        } else {
                            $resultMessage = "Friendship over";
                        }
                    }
                }
            } else if (isset($request->postParams['action-add'])) {
                // Add friend
                $targetPlayerId = intval($request->postParams['action-add']);

                if ($targetPlayerId) {
                    $targetPlayer = Player::fetch($targetPlayerId);

                    if ($targetPlayer && PlayerFriend::sendFriendRequest($selfPlayer, $targetPlayer)) {
                        $resultMessage = "Friend request sent to {$targetPlayer->getDisplayName()}";
                    }
                }
            }
        }

        if ($resultMessage && $paginatedSearch) {
            // Force reload after result
            $results = $paginatedSearch
                ->getPaginatedQuery()
                ->queryAllModels();
        }

        if ($resultMessage && $contextIsFriendsList) {
            // Redirect back to friendslist
            return new RedirectResponse($selfPlayer->getWebDetailUrl() . '/friends');
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View("pages/befriend.twig");
        $view->set('pageTitle', 'Add friends');
        $view->set('queryValue', $queryValue);
        $view->set('results', $results);
        $view->set('resultMessage', $resultMessage);
        $view->set('paginator', $paginatedSearch);
        $view->set('paginationBaseUrl', "/befriend?query=" . urlencode($queryValue));

        $response = $view->asResponse($isSubmission ? 422 : 200);
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

    private function loadFriendsData(Player $player)
    {
        $friendships = PlayerFriend::query()
            ->where('player_one_id = ? OR player_two_id = ?', $player->id, $player->id)
            ->queryAllModels();

        return $friendships;
    }
}