<?php

namespace app\Frontend;

use app\HTTP\Response;
use app\Session\Session;

class ResponseCache
{
    private string $cacheKey;
    private ?int $maxTtl;

    public function __construct(string $cacheKey, ?int $maxTtl = 60)
    {
        $this->cacheKey = $cacheKey;
        $this->maxTtl = $maxTtl;
    }

    private static function getBaseDir(): string
    {
        return DIR_STORAGE . "/response_cache";
    }

    public function getFilePath()
    {
        return self::getBaseDir() . "/{$this->cacheKey}.res";
    }

    public function getIsAvailable(): bool
    {
        global $bssbConfig;
        if (!$bssbConfig['response_cache_enabled']) {
            // Response cache is disabled, force unavailable status
            return false;
        }

        if ($this->getIsUserLoggedIn())
            // User is authed - does not participate in response cache
            return false;

        if (defined('FORCE_CACHE_GEN') && FORCE_CACHE_GEN)
            // Forcing cache regen; do not return available cache
            return false;

        $filePath = $this->getFilePath();

        if (!file_exists($filePath) || filesize($filePath) === 0) {
            // File does not exist or is empty
            return false;
        }

        if ($this->maxTtl !== null){
            // A max TTL is defined
            $lifeTime = time() - filemtime($filePath);

            if ($lifeTime >= $this->maxTtl) {
                // The file exists the time-to-live limit
                return false;
            }
        }

        // Looks good, no problems found
        return true;
    }

    public function read(): string
    {
        return file_get_contents($this->getFilePath());
    }

    public function readAsResponse(int $responseCode = 200, string $contentType = "text/html"): Response
    {
        return new Response($responseCode, $this->read(), $contentType);
    }

    public function write(string $value): bool
    {
        if ($this->getIsUserLoggedIn())
            // User is authed - does not participate in response cache
            return false;

        return file_put_contents($this->getFilePath(), $value) > 0;
    }

    public function writeResponse(Response $response): bool
    {
        if ($this->getIsUserLoggedIn())
            // User is authed - does not participate in response cache
            return false;

        return $this->write($response->body);
    }

    public function getIsUserLoggedIn(): bool
    {
        return Session::getInstance()->getIsSteamAuthed();
    }
}