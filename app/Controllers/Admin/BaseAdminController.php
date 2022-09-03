<?php

namespace app\Controllers\Admin;

use app\BSSB;
use app\Frontend\ViewRenderer;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Models\Player;
use app\Session\Session;

abstract class BaseAdminController
{
    private ?Session $session;
    private ?Player $selfUser;

    public function before(Request $request): ?Response
    {
        // Session/auth
        $this->session = BSSB::getSession();
        $this->selfUser = null;

        if ($this->session->getIsSteamAuthed())
            $this->selfUser = $this->session->getPlayer();

        if (!$this->selfUser || !$this->selfUser->getIsSiteAdmin())
            return new RedirectResponse('/?you_are_not_authed_go_away');

        // CSRF
        $csrfToken = $this->refreshCsrfToken();

        if ($request->method === "POST") {
            $csrfInput = $request->postParams['__csrf'];

            if ($csrfInput !== $csrfToken) {
                return new Response(400, "CSRF validation error");
            }

            $this->refreshCsrfToken(forceRegen: true);
        }

        return null;
    }

    private function refreshCsrfToken(bool $forceRegen = false): string
    {
        $csrfToken = $this->session->get('admin_csrf');

        if ($forceRegen || empty($csrfToken)) {
            $csrfToken = bin2hex(random_bytes(16));
            $this->session->set('admin_csrf', $csrfToken);
        }

        ViewRenderer::instance()->setGlobal('csrf', $csrfToken);
        return $csrfToken;
    }
}