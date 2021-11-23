<?php

namespace app\External;

use MaxMind\Db\Reader;

require_once __DIR__ . "/geoip2/geoip2.phar";

final class GeoIp
{
    private ?Reader $reader;

    public function __construct()
    {
        try {
            $this->reader = new Reader(__DIR__ . '/geoip2/GeoLite2-City.mmdb');
        } catch (\Exception) {
            $this->reader = null;
        }
    }

    public function lookupRecord(string $ipOrEndpoint): ?array
    {
        if (!$this->reader) {
            return null;
        }

        if (str_contains($ipOrEndpoint, ':')) {
            // Remove port if this is an endpoint string
            $parts = explode(':', $ipOrEndpoint, 2);
            $ip = $parts[0];
        } else {
            $ip = $ipOrEndpoint;
        }

        try {
            return $this->reader->get($ip);
        } catch (\Exception) {
            return null;
        }
    }

    public function getCountryCode(string $ipOrEndpoint): ?string
    {
        if ($record = $this->lookupRecord($ipOrEndpoint)) {
            return $record['country']['iso_code'];
        }
        return null;
    }

    public function describeLocation(string $ipOrEndpoint): ?string
    {
        if ($record = $this->lookupRecord($ipOrEndpoint)) {
            $parts = [];

            // Subdivision (state)
            if (isset($record['subdivisions'])) {
                foreach ($record['subdivisions'] as $subdivision) {
                    $parts[] = $subdivision['names']['en'];
                }
            }

            // Country name
            $parts[] = $record['country']['names']['en'];

            return implode(', ', $parts);
        }
        return null;
    }

}