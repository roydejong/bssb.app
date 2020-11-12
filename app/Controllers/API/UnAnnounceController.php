<?php

namespace app\Controllers\API;

use app\HTTP\Request;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;

class UnAnnounceController
{
    public function unAnnounce(Request $request)
    {
        // -------------------------------------------------------------------------------------------------------------
        // Input

        $serverCode = $request->queryParams['serverCode'] ?? null; // unused right now because what's the point
        $ownerId = $request->queryParams['ownerId'] ?? null;

        // -------------------------------------------------------------------------------------------------------------
        // Execute

        $anyDeletes = false;

        if ($ownerId) {
            if (HostedGame::query()->delete()->where("owner_id = ?", $ownerId)->execute() > 0) {
                $anyDeletes = true;
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        return new JsonResponse([
            "result" => $anyDeletes ? "deleted" : "noop",
        ]);
    }
}