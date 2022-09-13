<?php

namespace app\Controllers;

use app\Data\Filters\GameVersionFilter;
use app\Data\Filters\ModdedLobbyFilter;
use app\Data\Filters\ServerTypeFilter;
use app\Data\GameQuery;
use app\External\GeoIp;
use app\Frontend\ResponseCache;
use app\Frontend\View;
use app\HTTP\QueryParamTransform;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Models\Changelog;
use app\Models\SystemConfig;

class HomeController
{
    const CACHE_KEY = "home_page";
    const CACHE_TTL = 90;

    public function index(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Frontpage Cache

        $enableCache = empty($request->queryParams);

        if ($enableCache) {
            $resCache = new ResponseCache(self::CACHE_KEY, self::CACHE_TTL);

            if ($resCache->getIsAvailable()) {
                return $resCache->readAsResponse();
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Input

        $currentUrlNoPagination = QueryParamTransform::fromRequest($request)
            ->unset("page")
            ->toUrl();

        $pageIndex = intval($request->queryParams['page'] ?? 1) - 1;

        if ($pageIndex < 0)
            return new RedirectResponse($currentUrlNoPagination);

        // -------------------------------------------------------------------------------------------------------------
        // Query

        $gameQuery = new GameQuery();

        $gameQuery->addFilter(new GameVersionFilter());
        $gameQuery->addFilter(new ServerTypeFilter());
        $gameQuery->addFilter(new ModdedLobbyFilter());
        $gameQuery->applyFiltersFromRequest($request);

        $gameQuery->setPageSize(GameQuery::DefaultPageSize);
        $gameQuery->setPageIndex($pageIndex);

        $queryResult = $gameQuery->executeQuery();

        if (!$queryResult->isValidPage && $pageIndex > 1)
            return new RedirectResponse($currentUrlNoPagination);

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
        // News

        $changelogs = Changelog::query()
            ->orderBy('publish_date DESC, id DESC')
            ->limit(5)
            ->queryAllModels();

        // -------------------------------------------------------------------------------------------------------------
        // Render

        $view = new View('pages/home.twig');
        $view->set('serverMessage', (SystemConfig::fetchInstance())
            ->getCleanServerMessage());
        $view->set('queryResult', $queryResult);
        $view->set('games', $queryResult->games);
        $view->set('filters', $queryResult->filters);
        $view->set('filterOptions', $queryResult->filterOptions);
        $view->set('filterValues', $queryResult->filterValues);
        $view->set('isFiltered', $queryResult->getIsFiltered());
        $view->set('geoData', $geoData);
        $view->set('paginationBaseUrl', $currentUrlNoPagination);
        $view->set('changelogs', $changelogs);

        $response = $view->asResponse();

        if ($enableCache) {
            $resCache->writeResponse($response);
        }

        return $response;
    }
}