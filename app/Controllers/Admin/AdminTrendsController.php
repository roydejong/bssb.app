<?php

namespace app\Controllers\Admin;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Models\LevelRecord;

class AdminTrendsController extends BaseAdminController
{
    public function getTrends(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Filter switch

        $filterOptions = [
            "trends" => "Trending by score (customs)",
            "trendsall" => "Trending by score (all)"
        ];

        $queryFilter = $request->queryParams['filter'] ?? "trends";

        if (!isset($filterOptions[$queryFilter]))
            // Invalid filter in query
            return new RedirectResponse('/admin/trends');

        $filterName = $filterOptions[$queryFilter];

        // -------------------------------------------------------------------------------------------------------------
        // Query

        $levelsQuery = LevelRecord::query()
            ->limit(100);

        switch ($queryFilter) {
            default:
            case "trends":
                $levelsQuery
                    ->andWhere('hash IS NOT NULL')
                    ->orderBy('trend_factor DESC');
                break;
            case "trendsall":
                $levelsQuery
                    ->orderBy('trend_factor DESC');
                break;
        }

        $levels = $levelsQuery->queryAllModels();

        // -------------------------------------------------------------------------------------------------------------
        // Present

        $view = new View('admin/trends.twig');
        $view->set('title', $filterName);
        $view->set('tab', 'trends');
        $view->set('levels', $levels);
        $view->set('filters', $filterOptions);
        $view->set('selectedFilterId', $queryFilter);
        return $view->asResponse();
    }
}