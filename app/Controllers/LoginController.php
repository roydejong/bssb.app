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

        // Auth success
        $session->forceStart();
        $session->setSteamAuth($userSteamId);

        return new RedirectResponse('/me');
    }
}