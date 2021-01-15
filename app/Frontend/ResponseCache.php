<?php

namespace app\Frontend;

use app\HTTP\Response;

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
        return DIR_CACHE . "/responses";
    }

    public function getFilePath()
    {
        return self::getBaseDir() . "/{$this->cacheKey}.res";
    }

    public function getIsAvailable(): bool
    {
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
        return file_put_contents($this->getFilePath(), $value) > 0;
    }

    public function writeResponse(Response $response): bool
    {
        return $this->write($response->body);
    }
}