<?php

namespace app\Controllers\API;

use app\HTTP\Request;

class BrowseController
{
    public function browse(Request $request)
    {
        die("hi {$request->method} {$request->path}");
    }
}