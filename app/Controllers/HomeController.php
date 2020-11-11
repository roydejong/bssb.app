<?php

namespace app\Controllers;

use app\HTTP\IncomingRequest;

class HomeController
{
    public function index(IncomingRequest $request)
    {
        die("hi {$request->method} {$request->path}");
    }
}