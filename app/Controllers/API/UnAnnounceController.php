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

        if (!$request->getIsValidModClientRequest() || $request->method !== "POST") {
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Input

        $serverCode = $request->queryParams['serverCode'] ?? null; // unused right now because what's the point
        $ownerId = $request->queryParams['ownerId'] ?? null;

        $now = new \DateTime('now');

        // -------------------------------------------------------------------------------------------------------------
        // Validate

        if (empty($ownerId) || $ownerId === "SERVER_MESSAGE") {
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Execute

        $anyChanges = false;

        if ($ownerId) {
            if (HostedGame::query()
                ->update()
                ->set("ended_at = ?", $now)
                ->where('ended_at IS NULL')
                ->andWhere('owner_id = ?', $ownerId)
                ->execute() > 0) {
                $anyChanges = true;
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        return new JsonResponse([
            "result" => $anyChanges ? "ok" : "noop",
        ]);
    }
}