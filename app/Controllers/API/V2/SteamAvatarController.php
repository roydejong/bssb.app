<?php

namespace app\Controllers\API\V2;

use app\BSSB;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;

class SteamAvatarController
{
    public function steamAvatar(Request $request): Response
    {
        // Must be valid mod client request with JSON POST payload
        if (!$request->getIsValidClientRequest() || !$request->getIsJsonRequest()) {
            return new BadRequestResponse();
        }

        $input = $request->getJson();
        $steamUserId = $input['steamUserId'] ?? null;

        if (empty($steamUserId) || !ctype_digit($steamUserId) || strlen($steamUserId) > 17)
            return new Response(400, "Invalid Steam User ID");

        $cacheKey = "steamAvatar:$steamUserId";

        $redis = BSSB::getRedis();
        $cachedResult = $redis->getString($cacheKey);
        $liveResult = null;

        if ($cachedResult === null) {
            $liveResult = self::fetchSteamAvatarUrl($steamUserId);
            $redis->set($cacheKey, $liveResult, 3600);
        }

        return new Response(200, json_encode([
            "avatarUrl" => $liveResult ?? $cachedResult
        ]));
    }

    private static function fetchSteamAvatarUrl(string $steamUserId): ?string
    {
        global $bssbConfig;
        $steamWebApiKey = $bssbConfig["steam_web_api_key"] ?? null;

        $rawResponse = @file_get_contents(
            "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$steamWebApiKey}&steamids={$steamUserId}&format=json");

        if ($rawResponse === false)
            return null;

        $response = json_decode($rawResponse, true);
        if (!isset($response["response"]["players"][0]["avatarfull"]))
            return null;

        return $response["response"]["players"][0]["avatarfull"];
    }
}