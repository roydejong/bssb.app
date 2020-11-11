<?php

namespace app\HTTP;

use app\BeatSaber\ModClientInfo;

/**
 * An incoming HTTP request helper.
 */
class IncomingRequest
{
    // -----------------------------------------------------------------------------------------------------------------
    // Data

    /**
     * Request method (e.g. "GET", "POST").
     */
    public string $method = "";

    /**
     * Request hostname.
     */
    public string $host = "";

    /**
     * Request path, without query string.
     */
    public string $path = "";

    /**
     * Query parameters as associative array, all in original casing.
     */
    public array $queryParams = [];

    /**
     * Raw request headers as associative array, all keys lowercase.
     */
    public array $headers = [];

    // -----------------------------------------------------------------------------------------------------------------
    // JSON

    /**
     * Gets whether or not this request claims to have a JSON body.
     */
    public function getIsJsonRequest(): bool
    {
        if ($this->method === "POST") {
            // This request method may have a body
            if (strpos($this->headers["content-type"] ?? "", "application/json") === 0) {
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

    public function getModClientInfo(): ModClientInfo
    {
        return ModClientInfo::fromUserAgent($this->headers["user-agent"] ?? "");
    }

    public function getIsValidModClientRequest(): bool
    {
        if (empty($this->headers["x-bssb"])) {
            return false;
        }

        $mci = $this->getModClientInfo();

        if ($mci->modName !== "ServerBrowser" || empty($mci->assemblyVersion) || empty($mci->beatSaberVersion)) {
            return false;
        }

        return true;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Input

    /**
     * Extracts an IncomingRequest from superglobals.
     */
    public static function deduce(): IncomingRequest
    {
        $result = new IncomingRequest();

        // Core request information
        $result->method = $_SERVER['REQUEST_METHOD'];
        $result->host = $_SERVER['HTTP_HOST'];
        $result->path = strtok($_SERVER['REQUEST_URI'], '?'); // strtok to remove query string
        $result->queryParams = $_GET;

        // Request headers
        $result->headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, self::SV_HEADER_PREFIX) === 0) {
                $headerName = strtolower(substr($key, strlen(self::SV_HEADER_PREFIX)));
                $headerName = str_replace('_', '-', $headerName);
                $result->headers[$headerName] = $value;
            }
        }

        return $result;
    }

    private const SV_HEADER_PREFIX = 'HTTP_';
}