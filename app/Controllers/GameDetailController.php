<?php

namespace app\Controllers;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\RedirectResponse;
use app\Models\HostedGame;
use app\Models\LevelRecord;

class GameDetailController
{
    public function getGameDetail(Request $request, string $hashId)
    {
        $id = HostedGame::hash2id($hashId);

        if (!$id) {
            // Not a valid hash id
            return new BadRequestResponse();
        }

        $game = HostedGame::fetch($id);

        if (!$game) {
            // Not found, 404 redirect
            return new RedirectResponse('/', 404);
        }

        $level = null;

        if ($game->levelId) {
            $level = LevelRecord::query()
                ->where('level_id = ?', $game->levelId)
                ->querySingleModel();
        }

        $view = new View('game_detail.twig');
        $view->set('game', $game);
        $view->set('level', $level);
        return $view->asResponse();
    }
}