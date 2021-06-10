<?php

namespace app\Utils;

class PirateDetect
{
    private static array $pirateUserIds = ["mqsC892YHEEG91QeFPnNN1"];
    private static array $pirateUserNames = ["ALI213"];

    public static function detect(string $userId, string $userName): bool
    {
        if (in_array($userId, self::$pirateUserIds)) {
            return true;
        }
        if (in_array($userName, self::$pirateUserNames)) {
            return true;
        }
        return false;
    }
}