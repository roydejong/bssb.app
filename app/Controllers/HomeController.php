<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\IncomingRequest;
use app\Models\HostedGame;

class HomeController
{
    public function index(IncomingRequest $request)
    {
        $games = HostedGame::all();

        $view = new View('home.twig');
        $view->set('games', $games);
        return $view->asResponse();
    }
}