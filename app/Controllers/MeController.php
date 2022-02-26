<?php

namespace app\Controllers;

use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Models\Player;
use app\Session\Session;

class MeController
{
    public function getMe(): Response
    {
        $session = Session::getInstance();

        if (!$session->getIsSteamAuthed())
            // Not authed!
            return new RedirectResponse('/login');

        $player = Player::fromSteamId($session->getSteamUserId64());
        $session->setPlayerInfo($player);
        return new RedirectResponse($player->getWebDetailUrl());
    }
}