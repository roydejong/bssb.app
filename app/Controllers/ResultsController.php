<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\NotFoundResponse;
use app\Models\LevelHistory;

class ResultsController
{
    public function getResultsDetail(Request $request, string $guid): Response
    {
        /**
         * @var $levelHistory LevelHistory|null
         */
        $levelHistory = null;

        if ($guid) {
            $levelHistory = LevelHistory::query()
                ->where('session_game_id = ?', $guid)
                ->querySingleModel();
        }

        if (!$levelHistory) {
            return new NotFoundResponse();
        }

        $game = $levelHistory->fetchHostedGame();
        $level = $levelHistory->fetchLevelRecord();

        if (!$game || !$level) {
            return new NotFoundResponse();
        }

        $playerResults = $levelHistory->fetchPlayerResults();

        // -------------------------------------------------------------------------------------------------------------

        $view = new View("pages/results-detail.twig");
        $view->set('pageUrl', "/results/{$levelHistory->sessionGameId}");
        $view->set('pageTitle', "{$level->describeSong()} - {$game->gameName}");
        $view->set('pageDescr', "Multiplayer level results for {$level->describeSong()} in {$game->gameName}");
        $view->set('levelHistory', $levelHistory);
        $view->set('game', $game);
        $view->set('level', $level);
        $view->set('playerResults', $playerResults);
        $view->set('noIndex', true);
        return $view->asResponse();
    }
}