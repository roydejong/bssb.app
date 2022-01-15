<?php

namespace app\Controllers\API\V1;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\JsonResponse;
use SoftwarePunt\Instarecord\Instarecord;

class StatusController
{
    public function getStatus(Request $request): Response
    {
        $dbOk = false;

        try {
            $connection = Instarecord::connection(true);
            $connection->open();

            $dbOk = $connection->isOpen();
        } catch (\Exception $ex) {
            \Sentry\captureException($ex);
        }

        return new JsonResponse([
            "status" => $dbOk ? "ok" : "db_error"
        ]);
    }
}