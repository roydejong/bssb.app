<?php

namespace app\Controllers\API\V1;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;

class BrowseServerCodeController
{
    public function browseServerCode(Request $request, string $serverCode): Response
    {
        $serverCode = trim(strtoupper($serverCode));

        if (strlen($serverCode) !== 5 || !ctype_alnum($serverCode))
            return new BadRequestResponse("Server code parameter is invalid: must be 5 alphanumeric characters");

        /**
         * @var $matchingGames HostedGame[]
         */
        $matchingGames = HostedGame::query()
            ->where('server_code = ?', $serverCode) // Must match server code
            ->andWhere("last_update >= ?", HostedGame::getStaleGameCutoff()) // Must not be stale
            ->andWhere("ended_at IS NULL") // Must not be ended
            ->queryAllModels();

        $response = [
            'serverCode' => $serverCode,
            'results' => []
        ];

        foreach ($matchingGames as $game) {
            $response['results'][] = $game->jsonSerialize(false);
        }

        return new JsonResponse($response);
    }
}