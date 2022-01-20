<?php

namespace app\Common;

use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;

class IPEndPoint implements IDatabaseSerializable, \JsonSerializable
{
    public ?string $host;
    public ?int $port;

    public function __construct(?string $host = null, ?int $port = null)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function getIsIPv6(): bool
    {
        return filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function dbSerialize(): string
    {
        if ($this->getIsIPv6())
            return "[{$this->host}]:{$this->port}";

        return "{$this->host}:{$this->port}";
    }

    public function dbUnserialize(string $storedValue): void
    {
        if (str_starts_with($storedValue, '[') && str_contains($storedValue, ']:')) {
            // Unpack IPv6 address
            $endBracketIdx = strrpos($storedValue, ']:');

            $this->host = substr($storedValue, 1, ($endBracketIdx - 1));
            $this->port = intval(substr($storedValue, ($endBracketIdx + 2)));
        } else {
            // Default mode: IPv4 address or hostname
            $parts = explode(':', $storedValue, 2);

            $this->host = $parts[0];
            $this->port = intval($parts[1] ?? 0);
        }
    }

    public static function tryParse(?string $value): ?IPEndPoint
    {
        if (!$value)
            return null;

        $endpoint = new IPEndPoint();
        $endpoint->dbUnserialize($value);

        if ($endpoint->host && $endpoint->port > 0)
            return $endpoint;

        return null;
    }

    public function jsonSerialize(): mixed
    {
        return $this->dbSerialize();
    }

    public function __toString(): string
    {
        return self::dbSerialize();
    }
}