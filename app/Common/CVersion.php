<?php

namespace app\Common;

final class CVersion
{
    public ?int $major = null;
    public ?int $minor = null;
    public ?int $build = null;
    public ?int $revision = null;

    public function __construct(string $value)
    {
        $parts = explode('.', $value);
        $partCount = count($parts);

        for ($i = 0; $i < $partCount; $i++) {
            $part = intval($parts[$i]);

            if ($i === 0) {
                $this->major = $part;
            } else if ($i === 1) {
                $this->minor = $part;
            } else if ($i === 2) {
                $this->build = $part;
            } else if ($i === 3) {
                $this->revision = $part;
            } else {
                break;
            }
        }
    }

    public function equals(CVersion $b): bool
    {
        return intval($this->major) === intval($b->major) &&
            intval($this->minor) === intval($b->minor) &&
            intval($this->build) === intval($b->build) &&
            intval($this->revision) === intval($b->revision);
    }

    public function greaterThan(CVersion $b): bool
    {
        if ($this->major > $b->major) {
            return true;
        } else if ($this->major === $b->major) {
            if ($this->minor > $b->minor) {
                return true;
            } else if ($this->minor === $b->minor) {
                if ($this->build > $b->build) {
                    return true;
                } else if ($this->build === $b->build) {
                    if ($this->revision > $b->revision) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function greaterThanOrEquals(CVersion $b): bool
    {
        return $this->equals($b) || $this->greaterThan($b);
    }

    public function lessThan(CVersion $b): bool
    {
        return $b->greaterThan($this);
    }

    public function lessThanOrEquals(CVersion $b): bool
    {
        return $b->equals($this) || $b->greaterThan($this);
    }

    public function toString(int $maxDepth = PHP_INT_MAX): string
    {
        $parts = [$this->major, $this->minor, $this->build ?? null, $this->revision ?? null];
        $partCount = count($parts);

        $versionStr = "";

        for ($i = 0; ($i < $partCount && $i < $maxDepth); $i++) {
            $part = $parts[$i];

            if ($part === null) {
                break;
            }

            if (strlen($versionStr) > 0) {
                $versionStr .= '.';
            }

            $versionStr .= $part;
        }

        return $versionStr;
    }

    public function __toString(): string
    {
        return $this->toString(PHP_INT_MAX);
    }
}