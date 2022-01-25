<?php

namespace app\HTTP\Responses;

use app\HTTP\Response;

class BadRequestResponse extends Response
{
    public function __construct(?string $customErrorText = null)
    {
        parent::__construct(400, $customErrorText ?? "Bad request! Naughty!", "text/plain");
    }
}