<?php

namespace app\BeatSaber;

class LevelId
{
    const CUSTOM_LEVEL_PREFIX = "custom_level_";
    const HASH_LENGTH = 40;

    public static function getHashFromLevelId(string $levelId): ?string
    {
        if (self::isCustomLevel($levelId)) {
            $parts = explode(self::CUSTOM_LEVEL_PREFIX, $levelId, 2);
            $remainder = $parts[1] ?? null;

            if ($remainder && strlen($remainder) >= self::HASH_LENGTH) {
                $hash = substr($remainder, 0, self::HASH_LENGTH);

                if (ctype_alnum($hash)) {
                    // This looks like it could be a valid SHA-1 hash
                    return $hash;
                }
            }
        }

        // Level ID does not seem to contain a valid hash
        return null;
    }

    public static function cleanLevelHash(string $levelId): string
    {
        if (self::isCustomLevel($levelId)) {
            $hash = self::getHashFromLevelId($levelId);
            return self::CUSTOM_LEVEL_PREFIX . $hash;
        }

        return $levelId;
    }

    /**
     * Gets whether the given level id indicates a custom level.
     */
    public static function isCustomLevel(string $levelId): bool
    {
        return strpos($levelId, self::CUSTOM_LEVEL_PREFIX) === 0 &&
            strlen($levelId) >= (strlen(self::CUSTOM_LEVEL_PREFIX) + self::HASH_LENGTH);
    }
}