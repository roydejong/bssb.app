<?php

namespace app\Controllers\Admin;

use app\BSSB;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;

class TwitterCallbackController extends BaseAdminController
{
    public function handleCallback(Request $request): Response
    {
        $result = BSSB::getTweetinator()->handleOauthCallback($request);
        $resultCode = $result ? "success" : "error";

        return new RedirectResponse("/admin/connections?from=twitter_callback&result={$resultCode}");
    }
}