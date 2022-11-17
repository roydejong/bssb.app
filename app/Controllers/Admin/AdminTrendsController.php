<?php

namespace app\Controllers\Admin;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\LevelRecord;

class AdminTrendsController extends BaseAdminController
{
    public function getTrends(Request $request): Response
    {
        $levels = LevelRecord::query()
            ->where('hash IS NOT NULL')
            ->limit(100)
            ->orderBy('trend_factor DESC')
            ->queryAllModels();

        $view = new View('admin/trends.twig');
        $view->set('title', 'Trends');
        $view->set('tab', 'trends');
        $view->set('levels', $levels);
        return $view->asResponse();
    }
}