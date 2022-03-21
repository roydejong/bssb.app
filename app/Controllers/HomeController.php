<?php

namespace app\Controllers;

use app\Data\Filters\GameVersionFilter;
use app\Data\Filters\ModdedLobbyFilter;
use app\Data\Filters\ServerTypeFilter;
use app\Data\GameQuery;
use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\Request;
use app\Models\SystemConfig;

class HomeController
{
    const CACHE_KEY = "home_page";
    const CACHE_TTL = 60;

    public function index(Request $request)
    {
        $enableCache = empty($request->queryParams);

        if ($enableCache) {
            $resCache = new ResponseCache(self::CACHE_KEY, self::CACHE_TTL);

            if ($resCache->getIsAvailable()) {
                return $resCache->readAsResponse();
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Query

        $gameQuery = new GameQuery();

        $gameQuery->addFilter(new GameVersionFilter());
        $gameQuery->addFilter(new ServerTypeFilter());
        $gameQuery->addFilter(new ModdedLobbyFilter());

        $gameQuery->applyFiltersFromRequest($request);
        $queryResult = $gameQuery->execute();

        // -------------------------------------------------------------------------------------------------------------
        // GeoIP

        $geoIp = new GeoIp();
        $geoData = [];

        foreach ($queryResult->games as $game) {
            $endpoint = (string)$game->endpoint;

            if (!$endpoint || isset($geoData[$endpoint])) {
                continue;
            }

            $geoData[$endpoint] = [
                'countryCode' => $geoIp->getCountryCode($endpoint),
                'text' => $geoIp->describeLocation($endpoint)
            ];
        }

        // -------------------------------------------------------------------------------------------------------------
        // Render

        $view = new View('pages/home.twig');
        $view->set('serverMessage', (SystemConfig::fetchInstance())
            ->getCleanServerMessage());
        $view->set('games', $queryResult->games);
        $view->set('filters', $queryResult->filters);
        $view->set('filterOptions', $queryResult->filterOptions);
        $view->set('filterValues', $queryResult->filterValues);
        $view->set('isFiltered', $queryResult->getIsFiltered());
        $view->set('geoData', $geoData);

        $response = $view->asResponse();

        if ($enableCache) {
            $resCache->writeResponse($response);
        }

        return $response;
    }
}