<?php

namespace app\Controllers;

use app\Common\CVersion;
use app\Frontend\View;
use app\HTTP\Response;

class GuideController
{
    private const LastUpdate = "2022-03-02 21:30";
    private const CurrentGameVersion = "1.19.1";
    private const MinSupportedGameVersion = "1.17.1";
    private static $allGameVersions = [""];

    public function getGuideIndex(): Response
    {
        $currentGameVersion = new CVersion(self::CurrentGameVersion);

        $view = new View('pages/guide.twig');
        $view->set('pageTitle', "Multiplayer Modding Guide");
        $view->set('pageDescr', "An interactive guide that will help you play custom songs in Beat Saber multiplayer.");
        $view->set('lastUpdate', self::LastUpdate);
        $view->set('currentGameVersion', $currentGameVersion);
        return $view->asResponse();
    }
}