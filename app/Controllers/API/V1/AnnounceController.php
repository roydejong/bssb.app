<?php

namespace app\Controllers\API\V1;

use app\Data\AnnounceException;
use app\Data\AnnounceProcessor;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;
use function app\Controllers\API\ctype_alnum;

class AnnounceController
{
    public function announce(Request $request): Response
    {
        global $bssbConfig;

        // -------------------------------------------------------------------------------------------------------------
        // Input

        $isClientRequest = $request->getIsValidModClientRequest();
        $isDediRequest = $request->getIsValidBeatNetRequest();

        if ((!$isClientRequest && !$isDediRequest)
            || !$request->getIsJsonRequest()
            || $request->method !== "POST") {
            return new BadRequestResponse();
        }

        $modClientInfo = $request->getModClientInfo();
        $input = $request->getJson();

        // -------------------------------------------------------------------------------------------------------------
        // Process

        /**
         * @var $gameResult HostedGame|null
         */
        $gameResult = null;
        $userMessage = null;

        try {
            $processor = new AnnounceProcessor($modClientInfo, $input);
            $gameResult = $processor->process();
            $userMessage = $processor->getUserMessage();
        } catch (AnnounceException $ex) {
            return new JsonResponse([
                "success" => false,
                "message" => $ex->getMessage()
            ], responseCode: 400);
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        if ($gameResult) {
            return new JsonResponse([
                "success" => true,
                "key" => $gameResult->getHashId(),
                "message" => $userMessage
            ], responseCode: 200);
        } else {
            return new JsonResponse([
                "success" => false,
                "message" => "Game not created due to temporary problem, please try again"
            ], responseCode: 500);
        }
    }
}