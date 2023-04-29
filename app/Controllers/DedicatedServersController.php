<?php

namespace app\Controllers;

use app\Common\RemoteEndPoint;
use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Models\HostedGame;
use DateTime;

class DedicatedServersController
{
    const CACHE_KEY_PREFIX = "dedicated_servers_";
    const CACHE_TTL = 90;

    const CUTOFF_DAYS = 3;

    const PageSize = 100;

    public function getServerList(Request $request): Response
    {
        $baseUrl = "/dedicated-servers";

        // -------------------------------------------------------------------------------------------------------------
        // Input

        $pageIndex = intval($request->queryParams['page'] ?? 1) - 1;

        // -------------------------------------------------------------------------------------------------------------
        // Cache

        $cacheEnabled = false;
        $resCache = null;

        if ($pageIndex > 0) {
            $cacheKey = self::CACHE_KEY_PREFIX . $pageIndex;
            $resCache = new ResponseCache($cacheKey, self::CACHE_TTL);

            if ($resCache->getIsAvailable()) {
                return $resCache->readAsResponse();
            }

            $cacheEnabled = true;
        }

        // -------------------------------------------------------------------------------------------------------------
        // Query

        $cutoffDays = self::CUTOFF_DAYS;
        $activityCutoff = new DateTime("-{$cutoffDays} days");

        $dedicatedServersQuery = HostedGame::query()
            ->select('*')
            ->from('hosted_games hg1')
            ->innerJoin('(SELECT MAX(last_update) max_last_update, endpoint FROM hosted_games WHERE endpoint IS NOT NULL AND server_type IS NOT NULL AND server_type != ? GROUP BY endpoint) hg2 ON (hg1.endpoint = hg2.endpoint AND hg1.last_update = hg2.max_last_update)',
                HostedGame::SERVER_TYPE_PLAYER_HOST)
            ->andWhere('hg1.endpoint IS NOT NULL')
            ->andWhere('hg1.server_type IS NOT NULL AND hg1.server_type != ?', HostedGame::SERVER_TYPE_PLAYER_HOST)
            ->andWhere('hg1.endpoint NOT LIKE ?', "127.%")
            ->andWhere('hg1.endpoint NOT LIKE ?', "192.%")
            ->andWhere('last_update >= ?', $activityCutoff)
            ->groupBy('hg1.endpoint')
            ->orderBy('last_update DESC');

        $paginator = $dedicatedServersQuery
            ->paginate()
            ->setQueryPageSize(self::PageSize)
            ->setPageIndex($pageIndex);

        if (!$paginator->getIsValidPage()) {
            return new RedirectResponse($baseUrl);
        }

        $dedicatedServers = $paginator
            ->getPaginatedQuery()
            ->queryAllModels();

        // -------------------------------------------------------------------------------------------------------------
        // GeoIP

        $geoIp = new GeoIp();
        $geoData = [];

        foreach ($dedicatedServers as $dedicatedServer) {
            $endpoint = (string)$dedicatedServer->endpoint;

            if (isset($geoData[$endpoint]))
                continue;

            $endpointParsed = RemoteEndPoint::tryParse($endpoint);

            if (!$endpointParsed)
                continue;

            if ($endpointParsed->getHostIsDnsName())
                if (!$endpointParsed->tryResolve())
                    continue;

            $geoData[$endpoint] = [
                'countryCode' => $geoIp->getCountryCode($endpointParsed->host),
                'text' => $geoIp->describeLocation($endpointParsed->host)
            ];
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        $view = new View('pages/stats-dedicated-servers.twig');
        $view->set('pageUrl', "/dedicated-servers");
        $view->set('servers', $dedicatedServers);
        $view->set('geoData', $geoData);
        $view->set('cutoffDays', $cutoffDays);
        $view->set('paginationBaseUrl', $baseUrl);
        $view->set('paginator', $paginator);
        $view->set('pageTitle', "Dedicated Servers - Statistics");

        $response = $view->asResponse();
        $resCache?->writeResponse($response);
        return $response;
    }
}