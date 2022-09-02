<?php

namespace app\External;

use app\BSSB;
use MaxMind\Db\Reader;
use function Sentry\captureException;

require_once __DIR__ . "/geoip2/geoip2.phar";

final class GeoIp
{
    private const CacheTtlSeconds = 86400;

    private bool $dbLoaded;
    private ?Reader $reader;

    private static ?GeoIp $instance;

    public function __construct()
    {
        $this->dbLoaded = false;
        $this->reader = null;

        self::$instance = $this;
    }

    private function lazyLoad(): void
    {
        if ($this->dbLoaded)
            return;

        try {
            $this->reader = new Reader(__DIR__ . '/geoip2/GeoLite2-City.mmdb');
        } catch (\Exception $ex) {
            captureException($ex);
            $this->reader = null;
        }

        $this->dbLoaded = true;
    }

    public static function instance(): GeoIp
    {
        return self::$instance ?? new GeoIp();
    }

    private function lookupRecord(string $ipOrEndpoint): ?array
    {
        // Remove port if this is an endpoint string
        if (str_contains($ipOrEndpoint, ':')) {
            $parts = explode(':', $ipOrEndpoint, 2);
            $ip = $parts[0];
        } else {
            $ip = $ipOrEndpoint;
        }

        // Check for cached record
        $redis = BSSB::getRedis();

        $ipHash = md5($ip);
        $hashKey = "geoip:{$ipHash}";

        if ($cachedRecord = $redis->getArrayHash($hashKey))
            return $cachedRecord;

        // Load database if needed
        $this->lazyLoad();

        if (!$this->reader)
            return null;

        // Try get record, writing to cache if valid
        try {
            $recordReduced = self::reduceRecord($this->reader->get($ip));
            $redis->setArrayHash($hashKey, $recordReduced, self::CacheTtlSeconds);
            return $recordReduced;
        } catch (\Exception) {
            return null;
        }
    }

    private static function reduceRecord(?array $record): array
    {
        $reduced = [
            'valid' => $record !== null,
            'country_iso' => $record['country']['iso_code'] ?? null,
            'country_name' => $record['country']['names']['en'] ?? null,
            'subdivisions_text' => null
        ];

        if (isset($record['subdivisions'])) {
            $parts = [];
            foreach ($record['subdivisions'] as $subdivision)
                $parts[] = $subdivision['names']['en'];
            if (!empty($parts))
                $reduced['subdivisions_text'] = implode(', ', $parts);
        }

        return $reduced;
    }

    public function getCountryCode(string $ipOrEndpoint): ?string
    {
        if ($record = $this->lookupRecord($ipOrEndpoint)) {
            return $record['country_iso'];
        }
        return null;
    }

    public function describeLocation(string $ipOrEndpoint, bool $includeSubdivisons = true, bool $includeCountry = true): ?string
    {
        if ($record = $this->lookupRecord($ipOrEndpoint)) {
            $parts = [];

            if ($includeSubdivisons && !empty($record['subdivisions_text']))
                $parts[] = $record['subdivisions_text'];

            if ($includeCountry && !empty($record['country_name']))
                $parts[] = $record['country_name'];

            return implode(', ', $parts);
        }

        return null;
    }

}