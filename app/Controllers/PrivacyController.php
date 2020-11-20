<?php

namespace app\Controllers;

use app\Frontend\View;

class PrivacyController
{
    public function getPrivacy()
    {
        $view = new View('privacy.twig');
        return $view->asResponse();
    }
}