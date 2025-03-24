<?php

namespace app\Utils;

class PlayerBotDetect
{
    private static array $botPrefixes = ["BeatNet:", "DEVBOT/", "BottyMcBot/", "BOT#"];

    public static function detect(string $userId, string $userName): bool
    {
        foreach (self::$botPrefixes as $prefix) {
            if (str_starts_with($userId, $prefix)) {
                return true;
            }
        }
        return false;
    }
}