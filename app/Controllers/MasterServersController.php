<?php

namespace app\Controllers;

use app\External\MasterServerStatus;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\HostedGame;
use app\Models\MasterServerInfo;
use DateTime;

class MasterServersController
{
    const CACHE_KEY = "master_servers";
    const CACHE_TTL = 90;

    public function getServerList(Request $request): Response
    {
        $resCache = new ResponseCache(self::CACHE_KEY, self::CACHE_TTL);

        if ($resCache->getIsAvailable()) {
            return $resCache->readAsResponse();
        }

        // -------------------------------------------------------------------------------------------------------------

        $oneWeekAgo = new DateTime('-1 week');

        $masterServerHosts = HostedGame::query()
            ->select('DISTINCT master_server_host, master_server_port, MIN(first_seen) AS min_first_seen, MAX(last_update) AS max_last_update, COUNT(*) as game_count')
            ->where('master_server_host IS NOT NULL')
            ->andWhere('master_server_host NOT IN (?)', ["127.0.0.1", "localhost", "secret.dont.announce",
                "sekr.it"])
            ->andWhere('master_server_host NOT LIKE ?', "192.%")
            ->andWhere('master_server_host NOT LIKE ?', "%.beatsaber.com") // Official Master servers are offline and no longer resolve their DNS
            ->groupBy('master_server_host')
            ->orderBy('game_count DESC')
            ->queryAllRows();

        // -------------------------------------------------------------------------------------------------------------
        // 7 day count; hide servers without recent games & sort remaining servers

        $sevenDayGameCounts = HostedGame::query()
            ->select(' master_server_host, COUNT(*) game_count')
            ->groupBy('master_server_host')
            ->andWhere('last_update >= ?', $oneWeekAgo)
            ->queryKeyValueArray();

        $masterServerHostsFinal = [];
        foreach ($masterServerHosts as $masterServerHost) {
            $masterServerHostUrl = $masterServerHost['master_server_host'];
            $sevenDayCount = $sevenDayGameCounts[$masterServerHostUrl] ?? 0;
            if ($sevenDayCount <= 0)
                continue;
            $masterServerHostsFinal[] = $masterServerHost;
        }
        $masterServerHosts = $masterServerHostsFinal;

        usort($masterServerHosts, function (array $a, array $b) use ($sevenDayGameCounts): int {
            $sevenDayCountA = $sevenDayGameCounts[$a['master_server_host']] ?? 0;
            $sevenDayCountB = $sevenDayGameCounts[$b['master_server_host']] ?? 0;
            if ($sevenDayCountA === $sevenDayCountB) return 0;
            return $sevenDayCountA > $sevenDayCountB ? -1 : +1;
        });

        // -------------------------------------------------------------------------------------------------------------

        /**
         * @var $masterServerInfo MasterServerInfo[]
         */
        $masterServerInfo = MasterServerInfo::all();
        $masterServerInfoIndexed = [];

        foreach ($masterServerInfo as $info) {
            $key = "{$info->host}:{$info->port}";
            $masterServerInfoIndexed[$key] = $info;
        }

        // -------------------------------------------------------------------------------------------------------------

        $view = new View('pages/stats-master-servers.twig');
        $view->set('pageUrl', "/master-servers");
        $view->set('servers', $masterServerHosts);
        $view->set('sevenDayGameCounts', $sevenDayGameCounts);
        $view->set('masterServerInfo', $masterServerInfoIndexed);
        $view->set('pageTitle', "Master Servers - Statistics");
        $view->set('officialServiceEnv', MasterServerStatus::LiveGameServiceEnv);

        $response = $view->asResponse();
        $resCache->writeResponse($response);

        return $response;
    }
}