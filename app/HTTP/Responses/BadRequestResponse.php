<?php

namespace app\HTTP\Responses;

use app\HTTP\Response;

class BadRequestResponse extends Response
{
    public function __construct()
    {
        parent::__construct(400, "Bad request! Naughty!", "text/plain");
    }
}