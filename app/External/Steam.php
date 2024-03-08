<?php

namespace app\External;

use app\BSSB;
use app\External\Models\SteamAuthenticateUserTicketResponse;
use app\External\Models\SteamPlayerSummary;

class Steam
{
    /**
     * Tries to authenticate a user's ticket with the Steam Web API.
     * Will return the user's Steam ID if successful, or null if not.
     */
    public static function tryAuthenticateTicket(string $ticket): ?SteamAuthenticateUserTicketResponse
    {
        $response = self::makeApiRequest("/ISteamUserAuth/AuthenticateUserTicket/v1", [
            "appid" => 620980,
            "ticket" => $ticket
        ]);

        if ($response === null || !isset($response["response"]["params"]))
            return null;

        return new SteamAuthenticateUserTicketResponse($response["response"]["params"]);
    }

    public static function GetPlayerSummary(string $steamUserId, bool $allowCached = true): ?SteamPlayerSummary
    {
        $redis = BSSB::getRedis();
        $cacheKey = "steamPlayerSummary:$steamUserId";

        $playerData = null;

        if ($allowCached) {
            $cachedResult = $redis->getString($cacheKey);
            if ($cachedResult !== null) {
                $playerData = json_decode($cachedResult, true);
            }
        }

        if (!$playerData) {
            $response = self::makeApiRequest("/ISteamUser/GetPlayerSummaries/v0002", [
                "steamids" => $steamUserId
            ]);

            if ($response === null || !isset($response["response"]["players"][0]))
                return null;

            $playerData = $response["response"]["players"][0];
            $redis->set($cacheKey, json_encode($playerData), 3600);
        }

        return new SteamPlayerSummary($playerData);
    }

    public static function makeApiRequest(string $path, array $params): ?array
    {
        global $bssbConfig;
        $steamWebApiKey = $bssbConfig["steam_web_api_key"] ?? null;

        $path = rtrim($path, "/");
        $targetUrl = "https://api.steampowered.com{$path}/"
            . "?key={$steamWebApiKey}&" . http_build_query($params);

        $rawResponse = @file_get_contents($targetUrl);

        if ($rawResponse === false)
            return null;

        return json_decode($rawResponse, true);
    }
}