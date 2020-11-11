<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\IncomingRequest;

class HomeController
{
    public function index(IncomingRequest $request)
    {
        $view = new View('home.twig');
        return $view->asResponse();
    }
}