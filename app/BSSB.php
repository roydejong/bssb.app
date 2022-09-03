<?php

namespace app;

use app\Cache\RedisCache;
use app\External\Tweetinator;
use app\Models\SystemConfig;
use app\Session\Session;
use Hashids\Hashids;

class BSSB
{
    private static array $config;
    private static RedisCache $redisCache;
    private static array $hashIds;
    private static ?Tweetinator $tweetinator;

    public static function bootstrap()
    {
        global $bssbConfig;

        self::$config = $bssbConfig;
        self::$redisCache = new RedisCache();
        self::$hashIds = [];
        self::$tweetinator = null;
    }

    public static function getConfig(string $key): mixed
    {
        return self::$config[$key] ?? null;
    }

    public static function getSystemConfig(): SystemConfig
    {
        return SystemConfig::fetchInstance();
    }

    public static function getSession(): Session
    {
        return Session::getInstance();
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

    public static function getTweetinator(): Tweetinator
    {
        if (self::$tweetinator == null)
            self::$tweetinator = new Tweetinator(

            );

        return self::$tweetinator;
    }
}

BSSB::bootstrap();