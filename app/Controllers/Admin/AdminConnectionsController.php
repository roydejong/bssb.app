<?php

namespace app\Controllers\Admin;

use app\BSSB;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;

class AdminConnectionsController extends BaseAdminController
{
    public function getConnections(Request $request): Response
    {
        $twitter = BSSB::getTweetinator();

        if ($request->method === "POST") {
            $action = $request->postParams['action'];

            switch ($action) {
                case "connect_twitter": {
                    $authorizeUrl = $twitter->generateAuthorizeUrl();
                    if ($authorizeUrl) {
                        return new RedirectResponse($authorizeUrl);
                    }
                    break;
                }
            }
        }

        $view = new View('admin/connections.twig');
        $view->set('title', 'Connections');
        $view->set('tab', 'connections');
        $view->set('systemConfig', BSSB::getSystemConfig());
        return $view->asResponse();
    }
}