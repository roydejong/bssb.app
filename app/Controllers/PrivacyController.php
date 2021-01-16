<?php

namespace app\Controllers;

use app\Frontend\View;

class PrivacyController
{
    public function getPrivacy()
    {
        $view = new View('privacy.twig');
        $view->set('pageUrl', '/privacy');
        return $view->asResponse();
    }
}