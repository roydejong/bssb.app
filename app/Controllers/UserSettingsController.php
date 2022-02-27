<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Session\Session;

class UserSettingsController
{
    const CsrfKey = "user-settings";

    public function getUserSettings(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Fetch

        $session = Session::getInstance();
        $player = $session->getIsSteamAuthed() ? $session->getPlayer() : null;

        if (!$player)
            return new RedirectResponse('/login');

        // -------------------------------------------------------------------------------------------------------------
        // Edit

        $formData = [
            "profileBio" => $player->profileBio,
            "showSteam" => $player->showSteam,
            "showScoreSaber" => $player->showScoreSaber,
            "showHistory" => $player->showHistory
        ];

        $formError = false;

        if ($request->method === "POST") {
            foreach ($formData as $key => $value) {
                $formData[$key] = $request->postParams[$key] ?? null;
            }

            if (!$session->validateCsrfRequest($request, self::CsrfKey)) {
                // Invalid CSRF token
                $formError = true;
            }

            if (!$formError) {
                $player->profileBio = $formData['profileBio'];
                $player->showSteam = intval($formData['showSteam']) === 1;
                $player->showScoreSaber = intval($formData['showScoreSaber']) === 1;
                $player->showHistory = intval($formData['showHistory']) === 1;
                $player->save();

                return new RedirectResponse($player->getWebDetailUrl(), 303);
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('pages/user-settings.twig');
        $view->set('__token', $session->getCsrfToken(self::CsrfKey));
        $view->set('formData', $formData);
        $view->set('formError', $formError);
        return $view->asResponse($formError ? 422 : 200);
    }
}