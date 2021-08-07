<?php

namespace app\Common;

use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;

class IPEndPoint implements IDatabaseSerializable
{
    public ?string $host;
    public ?int $port;

    public function __construct(?string $host = null, ?int $port = null)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function dbSerialize(): string
    {
        return "{$this->host}:{$this->port}";
    }

    public function dbUnserialize(string $storedValue): void
    {
        $parts = explode(':', $storedValue, 2);

        $this->host = $parts[0];
        $this->port = intval($parts[1] ?? 0);
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

    public function __toString(): string
    {
        return self::dbSerialize();
    }
}