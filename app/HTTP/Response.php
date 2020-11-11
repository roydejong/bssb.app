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
        http_response_code($this->code);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        if ($this->body) {
            echo $this->body;
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Parser

    public static function fromCurl(string $headers, ?string $body): Response
    {
        $response = new Response();

        $headerLines = explode("\r\n", $headers);

        $statusLine = array_shift($headerLines);
        $statusLineParts = explode(' ', $statusLine, 3);

        if (count($statusLineParts) !== 3) {
            throw new \InvalidArgumentException("Could not parse invalid HTTP status line: {$statusLine}");
        }

        $response->code = intval($statusLineParts[1]);

        foreach ($headerLines as $header) {
            if (empty($header)) {
                continue;
            }

            $headerParts = explode(':', $header, 2);
            $response->headers[$headerParts[0]] = trim($headerParts[1] ?? '');
        }

        $response->body =$body ?? "";
        return $response;
    }
}