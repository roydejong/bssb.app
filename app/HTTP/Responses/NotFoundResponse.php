<?php

namespace app\HTTP\Responses;

use app\HTTP\Response;

class NotFoundResponse extends Response
{
    public function __construct()
    {
        parent::__construct(404, "Page not found", "text/plain");
    }
}