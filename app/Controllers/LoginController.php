<?php

namespace app\Controllers;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Session\Session;
use xPaw\Steam\SteamOpenID;

class LoginController
{
    private static function getRealm(Request $request): string
    {
        return "{$request->protocol}://{$request->host}";
    }

    private static function getReturnUrl(Request $request): string
    {
        return self::getRealm($request) . '/login/return';
    }

    public function getLogin(Request $request): Response
    {
        $session = Session::getInstance();
        $session->forceStart();

        if ($session->getIsSteamAuthed())
            // Already authed
            return new RedirectResponse('/me');

        $queryParams = http_build_query([
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.realm' => self::getRealm($request),
            'openid.return_to' => self::getReturnUrl($request)
        ]);
        $redirectUri = "https://steamcommunity.com/openid/login?" . $queryParams;

        return new RedirectResponse($redirectUri);
    }

    public function getLoginReturn(Request $request): Response
    {
        $session = Session::getInstance();

        if ($session->getIsSteamAuthed())
            // Already authed
            return new RedirectResponse('/me');

        if (!isset($request->queryParams['openid_claimed_id']))
            // Login cancel or bad request
            return new RedirectResponse('/');

        $userSteamId = SteamOpenID::ValidateLogin(self::getReturnUrl($request));

        if (!$userSteamId)
            // Validation failed, try again
            return new RedirectResponse('/login');

        // Try to get profile for username retrieval
        $steamUserName = self::tryGetSteamUsername($userSteamId);

        // Auth success
        $session->forceStart();
        $session->setSteamAuth($userSteamId, $steamUserName);

        // Redirect to profile
        return new RedirectResponse('/me');
    }

    private static function tryGetSteamUsername(string $userSteamId): ?string
    {
        global $bssbConfig;
        $steamWebApiKey = $bssbConfig["steam_web_api_key"] ?? null;

        if (!$steamWebApiKey)
            // No API key configured
            return null;

        $rawResponse = @file_get_contents(
            "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$steamWebApiKey}&steamids={$userSteamId}&format=json");

        if (!$rawResponse)
            // No response
            return null;

        $jsonParsed = @json_decode($rawResponse, true);

        if (!$jsonParsed || !is_array($jsonParsed) || empty($jsonParsed['response'])
            || empty($jsonParsed['response']['players']))
            // Invalid JSON / empty response
            return null;

        $playerObject = $jsonParsed['response']['players'][0];
        return $playerObject['personaname'] ?? null;
    }
}