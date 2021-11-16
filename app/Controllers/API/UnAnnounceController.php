<?php

namespace app\Controllers\API;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;

class UnAnnounceController
{
    public function unAnnounce(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Pre-flight checks

        if (!$request->getIsValidClientRequest() || $request->method !== "POST") {
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Input

        $serverCode = $request->queryParams['serverCode'] ?? null;
        $ownerId = $request->queryParams['ownerId'] ?? null;
        $hostSecret = $request->queryParams['hostSecret'] ?? null;

        $now = new \DateTime('now');

        // -------------------------------------------------------------------------------------------------------------
        // Validate

        if (empty($ownerId) || $ownerId === "SERVER_MESSAGE" || empty($serverCode)) {
            return new BadRequestResponse("ownerId is invalid");
        }

        // -------------------------------------------------------------------------------------------------------------
        // Execute

        $query = HostedGame::query()
            ->update()
            ->set("ended_at = ?", $now)
            ->andWhere('owner_id = ?', $ownerId)
            ->andWhere('server_code = ?', $serverCode)
            ->andWhere('ended_at IS NULL');

        if ($hostSecret) {
            $query->andWhere('host_secret = ?', $hostSecret);
        }

        $anyChanges = ($query->execute() > 0);

        // -------------------------------------------------------------------------------------------------------------
        // Response

        return new JsonResponse([
            "result" => $anyChanges ? "ok" : "noop",
        ]);
    }
}