<?php

namespace app\Controllers\API\V1;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\HTTP\Responses\NotFoundResponse;
use app\Models\HostedGame;

class BrowseDetailController
{
    public function browseDetail(Request $request, string $hashId): Response
    {
        if (!$request->getIsValidModClientRequest()) {
            return new BadRequestResponse();
        }

        /**
         * @var $game HostedGame|null
         */
        $game = null;

        if ($gameId = HostedGame::hash2id($hashId)) {
            $game = HostedGame::fetch($gameId);
        }

        if (!$game) {
            return new NotFoundResponse();
        }

        return new JsonResponse($game->jsonSerialize(true, true, true));
    }
}