<?php

namespace app\Controllers\API\V2;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\MasterServerInfo;

class ConfigController
{
    public function getConfig(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Preflight

        // Must be valid mod client POST request
        if (!$request->getIsValidClientRequest() || $request->method !== "POST")
            return new BadRequestResponse();

        // -------------------------------------------------------------------------------------------------------------
        // Data

        $lastSeenCutoff = new \DateTime('now');
        $lastSeenCutoff->modify('-1 week');

        /**
         * @var $masterServers MasterServerInfo[]
         */
        $masterServers = MasterServerInfo::query()
            ->where('hide = 0')
            ->andWhere('graph_url IS NOT NULL')
            ->andWhere('status_url IS NOT NULL')
            ->andWhere('is_official = 0')
            ->andWhere('last_status_json IS NOT NULL')
            ->queryAllModels();

        $masterServersSz = [];
        foreach ($masterServers as $masterServer)
            $masterServersSz[] = $masterServer->serializeForConfig();

        // -------------------------------------------------------------------------------------------------------------
        // Response

        return new JsonResponse([
            "master_servers" => $masterServersSz
        ]);
    }
}