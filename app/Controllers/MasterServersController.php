<?php

namespace app\Controllers;

use app\BeatSaber\MasterServer;
use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\HostedGame;
use DateTime;

class MasterServersController
{
    const CACHE_KEY = "master_servers";
    const CACHE_TTL = 30;

    public function getServerList(Request $request): Response
    {
        $resCache = new ResponseCache(self::CACHE_KEY, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        // -------------------------------------------------------------------------------------------------------------

        $masterServerHosts = HostedGame::query()
            ->select('DISTINCT master_server_host, master_server_port, MIN(first_seen) AS min_first_seen, MAX(last_update) AS max_last_update, COUNT(*) as game_count')
            ->where('master_server_host IS NOT NULL')
            ->andWhere('master_server_host NOT IN (?)', ["127.0.0.1", "localhost", "secret.dont.announce"])
            ->andWhere('master_server_host NOT LIKE ?', "192.%")
            ->groupBy('master_server_host')
            ->orderBy('game_count DESC')
            ->queryAllRows();

        $oneWeekAgo = new DateTime('-1 week');

        $sevenDayGameCounts = HostedGame::query()
            ->select(' master_server_host, COUNT(*) game_count')
            ->groupBy('master_server_host')
            ->andWhere('last_update >= ?', $oneWeekAgo)
            ->queryKeyValueArray();

        // -------------------------------------------------------------------------------------------------------------

        $geoIp = new GeoIp();

        $geoData = [];
        $resolvedHosts = [];

        foreach ($masterServerHosts as &$masterServer) {
            $hostName = $masterServer['master_server_host'];

            // Resolve hostname to IP
            if (isset($resolvedHosts[$hostName])) {
                $ipAddress = $resolvedHosts[$hostName];
            } else {
                $ipAddress = gethostbyname($hostName);
                $resolvedHosts[$hostName] = $ipAddress;
            }

            // Enrich master server data
            $masterServer['ip_address'] = $ipAddress;
            $masterServer['is_official'] = str_ends_with($hostName, MasterServer::OFFICIAL_HOSTNAME_SUFFIX);

            // Add GeoIP info for each IP
            if (isset($geoData[$ipAddress]))
                continue;
            $geoData[$ipAddress] = [
                'countryCode' => $geoIp->getCountryCode($ipAddress),
                'text' => $geoIp->describeLocation($ipAddress)
            ];
        }

        // -------------------------------------------------------------------------------------------------------------

        $view = new View('master-servers.twig');
        $view->set('servers', $masterServerHosts);
        $view->set('sevenDayGameCounts', $sevenDayGameCounts);
        $view->set('geoData', $geoData);

        $response = $view->asResponse();
        $resCache->writeResponse($response);

        return $response;
    }
}