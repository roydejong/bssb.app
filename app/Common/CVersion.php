<?php

namespace app\Common;

use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;

final class CVersion implements IDatabaseSerializable, \JsonSerializable
{
    public ?int $major = null;
    public ?int $minor = null;
    public ?int $build = null;
    public ?int $revision = null;

    public function __construct(?string $value = null)
    {
        $this->setValue($value);
    }

    public function setValue(?string $value): void
    {
        $parts = explode('.', $value ?? "");
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

    // -----------------------------------------------------------------------------------------------------------------
    // Serialization

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

    public function dbSerialize(): string
    {
        return $this->__toString();
    }

    public function dbUnserialize(string $storedValue): void
    {
        $this->setValue($storedValue);
    }

    public function jsonSerialize(): mixed
    {
        return $this->dbSerialize();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Static compare

    /**
     * Returns the highest of two versions.
     *
     * @param CVersion $a
     * @param CVersion $b
     * @return CVersion
     */
    public static function max(CVersion $a, CVersion $b): CVersion
    {
        return $a->greaterThan($b) ? $a : $b;
    }

    /**
     * Returns the lowest of two versions.
     *
     * @param CVersion $a
     * @param CVersion $b
     * @return CVersion
     */
    public static function min(CVersion $a, CVersion $b): CVersion
    {
        return $a->lessThan($b) ? $a : $b;
    }
}