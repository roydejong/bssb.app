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
            $hash = $parts[1] ?? null;

            if ($hash && strlen($hash) === self::HASH_LENGTH && ctype_alnum($hash)) {
                // This looks like it could be a valid SHA-1 hash
                return $parts[1];
            }
        }

        // Level ID does not seem to contain a valid hash
        return null;
    }

    /**
     * Gets whether the given level id indicates a custom level.
     */
    public static function isCustomLevel(string $levelId): bool
    {
        return strpos($levelId, self::CUSTOM_LEVEL_PREFIX) === 0 &&
            strlen($levelId) === (strlen(self::CUSTOM_LEVEL_PREFIX) + self::HASH_LENGTH);
    }
}