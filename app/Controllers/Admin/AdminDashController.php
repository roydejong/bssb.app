<?php

namespace app\Controllers\Admin;

use app\Frontend\View;
use app\HTTP\Response;

class AdminDashController extends BaseAdminController
{
    public function getDash(): Response
    {
        $view = new View('admin/dash.twig');
        $view->set('title', 'Dashboard');
        $view->set('tab', 'dash');
        return $view->asResponse();
    }
}