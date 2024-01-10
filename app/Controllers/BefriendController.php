<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\RedirectResponse;
use app\Models\Joins\PlayerRelationshipJoin;
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
        $view->set('pageUrl', "/befriend");
        $view->set('pageTitle', 'Add friends');
        $view->set('queryValue', $queryValue);
        $view->set('results', $results);
        $view->set('resultMessage', $resultMessage);
        $view->set('paginator', $paginatedSearch);
        $view->set('paginationBaseUrl', "/befriend?query=" . urlencode($queryValue));

        $response = $view->asResponse($isSubmission ? 422 : 200);
        return $response;
    }
}