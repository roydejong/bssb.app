<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\Models\HostedGame;
use app\Models\Joins\HostedGameLevelRecord;

class HomeController
{
    public function index(Request $request)
    {
        $updateCutoff = new \DateTime('now');
        $updateCutoff->modify('-10 minutes');

        /**
         * @var $games HostedGame[]
         */
        $games = HostedGameLevelRecord::query()
            ->select("hosted_games.*, lr.beatsaver_id, lr.cover_url, lr.name AS level_name")
            ->from("hosted_games")
            ->leftJoin("level_records lr ON (lr.level_id = hosted_games.level_id)")
            ->orderBy("hosted_games.id DESC")
            ->where("last_update >= ?", $updateCutoff)
            ->queryAllModels();

        $view = new View('home.twig');
        $view->set('games', $games);
        return $view->asResponse();
    }
}