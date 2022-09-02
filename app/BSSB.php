<?php

namespace app;

use app\Cache\RedisCache;
use Hashids\Hashids;

class BSSB
{
    private static RedisCache $redisCache;
    private static array $hashIds;

    public static function bootstrap()
    {
        self::$redisCache = new RedisCache();
        self::$hashIds = [];
    }

    public static function getRedis(): RedisCache
    {
        return self::$redisCache;
    }

    public static function getHashids(string $key): Hashids
    {
        global $bssbConfig;

        if (!isset(self::$hashIds[$key])) {
            self::$hashIds[$key] = new Hashids($bssbConfig['hashids_salt'] . $key, 3);
        }

        return self::$hashIds[$key];
    }
}

BSSB::bootstrap();