<?php

namespace app\BeatSaber;

class MultiplayerUserId
{
    const UserIdLength = 22;

    public static function hash(string $platformType, string $platformUserId): string
    {
        $platformTypeNormal = ModPlatformId::normalize($platformType);

        $prefixTable = [
            ModPlatformId::STEAM => "Steam",
            ModPlatformId::PS4 => "PSN",
            ModPlatformId::OCULUS => "Oculus"
        ];
        $prefix = $prefixTable[$platformTypeNormal] ?? null;

        if (!in_array($prefix, ["Test", "Oculus", "Steam", "PSN"], true))
            throw new \InvalidArgumentException("Invalid platform type or not in prefix lookup table: {$platformType}");

        /**
         * One-way hash process works as follows:
         *  1. Assemble input string with platform prefix and platform user id, e.g. "Steam#1234567890"
         *  2. Compute SHA256 hash (from UTF8 bytes)
         *  3. Encode result with Base64
         *  4. Take only the first 22 characters of the string
         */

        $input = "{$prefix}#{$platformUserId}";
        $sha256 = hash("sha256", $input, true);
        $b64 = base64_encode($sha256);
        return substr($b64, 0, self::UserIdLength);
    }
}