<?php

namespace app\HTTP;

use app\BeatSaber\ModClientInfo;
use app\Common\CString;

/**
 * An incoming HTTP request helper.
 */
class Request
{
    // -----------------------------------------------------------------------------------------------------------------
    // Data

    /**
     * Request method (e.g. "GET", "POST").
     */
    public string $method = "GET";

    /**
     * Request hostname.
     */
    public ?string $host = null;

    /**
     * Request path, without query string.
     */
    public string $path = "";

    /**
     * Query parameters as associative array, all in original casing.
     */
    public array $queryParams = [];

    /**
     * POST form parameters as associative array.
     */
    public array $postParams = [];

    /**
     * Raw request headers as associative array, all keys lowercase.
     */
    public array $headers = [];

    /**
     * The request protocol ("http" or "https").
     */
    public string $protocol = "http";

    /**
     * Raw request cookies as key-value array.
     */
    public array $cookies = [];

    public function __construct() { }

    // -----------------------------------------------------------------------------------------------------------------
    // URL helpers

    public function getUri(bool $includeQuery = true): string
    {
        $queryString = $this->getQueryString();
        $uri = "{$this->protocol}://{$this->host}{$this->path}";
        if ($includeQuery && $queryString) {
            $uri .= "?{$queryString}";
        }
        return $uri;
    }

    public function getQueryString(): string
    {
        return http_build_query($this->queryParams);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // JSON

    /**
     * Gets whether or not this request claims to have a JSON body.
     */
    public function getIsJsonRequest(): bool
    {
        if ($this->method === "POST") {
            // This request method may have a body
            if (CString::startsWith($this->headers["content-type"] ?? "", "application/json")) {
                // Request headers indicate this is a JSON request
                return true;
            }
        }

        return false;
    }

    /**
     * Attempts to parse and return the request body as an associative array.
     * Will return NULL if this does not appear to be a JSON request, or parsing fails.
     */
    public function getJson(): ?array
    {
        if ($this->getIsJsonRequest()) {
            $inputPath = defined('PHP_INPUT_URI') ? PHP_INPUT_URI : "php://input";
            $raw = @file_get_contents($inputPath);
            if ($raw) {
                $result = @json_decode($raw, true);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Mod client info

    private ?ModClientInfo $mci = null;

    public function getModClientInfo(): ModClientInfo
    {
        if (!$this->mci) {
            $this->mci = ModClientInfo::fromUserAgent($this->headers["user-agent"] ?? "");
        }

        return $this->mci;
    }

    public function getIsValidClientRequest(): bool
    {
        return $this->getIsValidModClientRequest()
            || $this->getIsValidBeatNetRequest();
    }

    public function getIsValidModClientRequest(): bool
    {
        if (empty($this->headers["x-bssb"])) {
            return false;
        }

        $mci = $this->getModClientInfo();

        if ($mci->modName !== ModClientInfo::MOD_SERVER_BROWSER_PC &&
            $mci->modName !== ModClientInfo::MOD_SERVER_BROWSER_QUEST)
        {
            // Invalid product name
            return false;
        }

        if (empty($mci->assemblyVersion) || empty($mci->beatSaberVersion)) {
            return false;
        }

        return true;
    }

    public function getIsValidBeatNetRequest(): bool
    {
        if (empty($this->headers["x-bssb"])) {
            return false;
        }

        $mci = $this->getModClientInfo();

        if ($mci->modName !== ModClientInfo::MOD_BEATNET || empty($mci->assemblyVersion) || empty($mci->beatSaberVersion)) {
            return false;
        }

        return true;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Input

    /**
     * Extracts an Request from superglobals.
     */
    public static function deduce(): Request
    {
        $result = new Request();

        // Core request information
        $result->method = $_SERVER['REQUEST_METHOD'];
        $result->host = $_SERVER['HTTP_HOST'] ?? null;
        $result->path = strtok($_SERVER['REQUEST_URI'], '?'); // strtok to remove query string
        $result->queryParams = $_GET;
        $result->postParams = $_POST;
        $result->protocol = !empty($_SERVER['HTTPS']) ? "https" : "http";
        $result->cookies = $_COOKIE;

        // Request headers
        $result->headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, self::SV_HEADER_PREFIX)) {
                $headerName = strtolower(substr($key, strlen(self::SV_HEADER_PREFIX)));
                $headerName = str_replace('_', '-', $headerName);
                $result->headers[$headerName] = $value;
            }
        }

        return $result;
    }

    private const SV_HEADER_PREFIX = 'HTTP_';

    // -----------------------------------------------------------------------------------------------------------------
    // Client

    public function send(): Response
    {
        $client = curl_init($this->getUri(true));

        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        curl_setopt_array($client, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_HTTPHEADER => $headers
        ]);

        try {
            // Execute request
            @$response = curl_exec($client);

            // We always prefer returning an HTTP response, even if it's an error, but w/o response data we'll throw.
            if (!$response) {
                $errNo = curl_errno($client);
                $errText = curl_strerror($errNo);

                throw new \Exception("cURL request error ($errNo): {$errText}");
            }

            // Extract headers and body response
            $headerSize = curl_getinfo($client, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            return Response::fromCurl($header, $body);
        } finally {
            curl_close($client);
        }
    }
}