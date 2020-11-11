<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\Models\HostedGame;

class HomeController
{
    public function index(Request $request)
    {
        /**
         * @var $games HostedGame[]
         */
        $games = HostedGame::query()
            ->orderBy('id DESC')
            ->queryAllModels();

        $view = new View('home.twig');
        $view->set('games', $games);
        return $view->asResponse();
    }
}