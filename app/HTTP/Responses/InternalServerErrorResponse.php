<?php

namespace app\HTTP\Responses;

use app\HTTP\Response;

class InternalServerErrorResponse extends Response
{
    public function __construct(string $message = "Something broke and I'm sorry")
    {
        parent::__construct(500, $message, "text/plain");
    }
}