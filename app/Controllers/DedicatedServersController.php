<?php

namespace app\Controllers;

use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\HostedGame;

class DedicatedServersController
{
    const CACHE_KEY = "dedicated_servers";
    const CACHE_TTL = 1;

    public function getServerList(Request $request): Response
    {
        $resCache = new ResponseCache(self::CACHE_KEY, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        // -------------------------------------------------------------------------------------------------------------

        $dedicatedServers = HostedGame::query()
            ->andWhere('endpoint IS NOT NULL')
            ->andWhere('server_type IS NOT NULL AND server_type != ?', [HostedGame::SERVER_TYPE_PLAYER_HOST])
            ->andWhere('endpoint NOT LIKE ?', "127.%")
            ->andWhere('endpoint NOT LIKE ?', "192.%")
            ->orderBy('last_update DESC')
            ->groupBy('endpoint')
            ->queryAllModels();

        $geoIp = new GeoIp();
        $geoData = [];

        foreach ($dedicatedServers as $dedicatedServer) {
            $endpoint = (string)$dedicatedServer->endpoint;

            if (isset($geoData[$endpoint])) {
                continue;
            }

            $geoData[$endpoint] = [
                'countryCode' => $geoIp->getCountryCode($endpoint),
                'text' => $geoIp->describeLocation($endpoint)
            ];
        }

        // -------------------------------------------------------------------------------------------------------------

        $view = new View('dedicated-servers.twig');
        $view->set('servers', $dedicatedServers);
        $view->set('geoData', $geoData);

        $response = $view->asResponse();
        $resCache->writeResponse($response);

        return $response;
    }
}