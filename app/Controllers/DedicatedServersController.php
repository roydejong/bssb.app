<?php

namespace app\Controllers;

use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\HostedGame;
use DateTime;

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

        $oneWeekAgo = new DateTime('-1 week');

        $dedicatedServers = HostedGame::query()
            ->select('*')
            ->from('hosted_games hg1')
            ->innerJoin('(SELECT MAX(last_update) max_last_update, endpoint FROM hosted_games WHERE endpoint IS NOT NULL AND server_type IS NOT NULL AND server_type != ? GROUP BY endpoint) hg2 ON (hg1.endpoint = hg2.endpoint AND hg1.last_update = hg2.max_last_update)',
                HostedGame::SERVER_TYPE_PLAYER_HOST)
            ->andWhere('hg1.endpoint IS NOT NULL')
            ->andWhere('hg1.server_type IS NOT NULL AND hg1.server_type != ?', HostedGame::SERVER_TYPE_PLAYER_HOST)
            ->andWhere('hg1.endpoint NOT LIKE ?', "127.%")
            ->andWhere('hg1.endpoint NOT LIKE ?', "192.%")
            ->andWhere('last_update >= ?', $oneWeekAgo)
            ->orderBy('last_update DESC')
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