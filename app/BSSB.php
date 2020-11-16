<?php

namespace app;

use Hashids\Hashids;

class BSSB
{
    private static $hashIds;

    public static function getHashids(string $key): Hashids
    {
        global $config;

        if (!isset(self::$hashIds[$key])) {
            self::$hashIds[$key] = new Hashids($config['hashids_salt'] . $key, 3);
        }

        return self::$hashIds[$key];
    }
}