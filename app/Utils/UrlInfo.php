<?php

namespace app\Utils;

/**
 * Utility for parsing, validating and (re)constructing URLs used by the game (graph, status, etc).
 */
class UrlInfo
{
    /**
     * The protocol used (always "http" or "https" for valid URLs).
     */
    public readonly string $protocol;
    /**
     * The HTTP(s) hostname.
     */
    public readonly string $host;
    /**
     * The HTTP(s) port.
     */
    public readonly int $port;
    /**
     * Indicates whether the URL appears to be a valid one, meaning:
     *  - Original input passes FILTER_VALIDATE_URL
     *  - URL is either "http" or "https"
     */
    public readonly bool $isValid;

    public function __construct(string $input)
    {
        $urlInfo = parse_url(strtolower($input));

        $this->protocol = $urlInfo['scheme'];
        $this->host = $urlInfo['host'];
        $this->port = intval($urlInfo['port'] ?? 80);
        $this->isValid = (filter_var($input, FILTER_VALIDATE_URL) !== false) &&
            ($this->protocol === "http" || $this->protocol === "https");
    }

    /**
     * Rebuilds the URL with only protocol (http/https), hostname, and any non-standard port number.
     * Any other parts of the URL will be stripped.
     */
    public function rebuild(): string
    {
        $url = "{$this->protocol}://{$this->host}";

        if ($this->protocol === "http" && $this->port !== 80)
            $url .= ":{$this->port}";

        if ($this->protocol === "https" && $this->port !== 443)
            $url .= ":{$this->port}";

        return $url;
    }

    public function __toString(): string
    {
        return $this->rebuild();
    }
}