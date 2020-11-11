<?php

namespace app\Controllers\API;

use app\HTTP\Request;

class UnAnnounceController
{
    public function unAnnounce(Request $request)
    {
        die("hi {$request->method} {$request->path}");
    }
}