<?php

namespace app\HTTP;

class Response
{
    // -----------------------------------------------------------------------------------------------------------------
    // Data

    /**
     * HTTP Response Code (e.g. 200 for OK)
     */
    public int $code;

    /**
     * Raw response body.
     */
    public ?string $body;

    /**
     * Associative array of response headers, all keys lowercase.
     */
    public array $headers;

    public function __construct(int $code = 200, ?string $body = null, ?string $contentType = null)
    {
        $this->code = $code;
        $this->body = $body;
        $this->headers = [];

        if ($contentType) {
            $this->headers["content-type"] = $contentType;
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Output

    /**
     * Sends the response, causing output.
     */
    public function send(): void
    {
        http_response_code(404);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        if ($this->body) {
            echo $this->body;
        }
    }
}