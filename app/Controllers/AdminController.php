<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\NotFoundResponse;
use app\HTTP\Responses\RedirectResponse;
use app\Models\SystemConfig;
use app\Session\Session;

class AdminController
{
    const CsrfKey = "admin-form";

    public function getAdminPage(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Session/auth

        $session = Session::getInstance();
        $user = $session->getIsSteamAuthed() ? $session->getPlayer() : null;

        if (!$user || !$user->getIsSiteAdmin())
            return new NotFoundResponse();

        // -------------------------------------------------------------------------------------------------------------
        // Form action

        $settings = SystemConfig::fetchInstance();

        $formData = [
            "serverMessage" => $settings->serverMessage
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
                $settings->serverMessage = $formData['serverMessage'];
                $settings->save();

                return new RedirectResponse("/admin?saved=1", 303);
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('pages/admin.twig');
        $view->set('__token', $session->getCsrfToken(self::CsrfKey));
        $view->set('formData', $formData);
        $view->set('formError', $formError);
        $view->set('tab', 'admin');
        return $view->asResponse($formError ? 422 : 200);
    }
}