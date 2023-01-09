<?php

namespace app\Controllers;

use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;

class DownloadController
{
    const CACHE_KEY = "downloads_page";
    const CACHE_TTL = 300;

    public function getDownloadPage(Request $request)
    {
        $resCache = new ResponseCache(self::CACHE_KEY, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        $view = new View('pages/download.twig');
        $view->set('pageUrl', '/download');
        $view->set('pageTitle', "Download Mod");
        $view->set('pageDescr', "Download the Server Browser mod to share and join multiplayer lobbies in Beat Saber.");
        $response = $view->asResponse();

        $resCache->writeResponse($response);
        return $response;
    }
}