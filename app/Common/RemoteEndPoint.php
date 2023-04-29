<?php

namespace app\Common;

use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;

/**
 * Represents a remote end point, either with an IP address or hostname.
 */
class RemoteEndPoint implements IDatabaseSerializable, \JsonSerializable
{
    /**
     * IP Address or hostname.
     */
    public ?string $host;
    /**
     * Port number.
     */
    public ?int $port;

    public function __construct(?string $host = null, ?int $port = null)
    {
        $this->host = $host;
        $this->port = $port;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Host

    public function getHostIsIpAddress(): bool
    {
        if ($this->host === null)
            return false;

        return $this->getIsIPv4() || $this->getIsIPv6();
    }

    public function getHostIsDnsName(): bool
    {
        if ($this->host === null)
            return false;

        return !$this->getHostIsIpAddress();
    }

    public function getIsIPv4(): bool
    {
        return filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function getIsIPv6(): bool
    {
        return filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function tryResolve(): bool
    {
        if (!$this->getHostIsDnsName())
            // Already resolved
            return true;

        $resolvedIp = @gethostbyname($this->host);

        if ($resolvedIp === $this->host)
            // Resolve failed
            return false;

        $this->host = $resolvedIp;
        return true;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

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

    public static function tryParse(?string $value): ?RemoteEndPoint
    {
        if (!$value)
            return null;

        $endpoint = new RemoteEndPoint();
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