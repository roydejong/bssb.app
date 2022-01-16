<?php

namespace app\Controllers\API\V2;

use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;

class UnAnnounceControllerV2
{
    public function unAnnounce(Request $request): Response
    {
        // Must be valid mod client request with JSON POST payload
        if (!$request->getIsValidClientRequest() || !$request->getIsJsonRequest()) {
            return new BadRequestResponse();
        }

        // Collect required data
        $input = $request->getJson();

        $selfUserId = $input["SelfUserId"] ?? null;
        $hostUserId = $input["HostUserId"] ?? null;
        $hostSecret = $input["HostSecret"] ?? null;

        if (!$selfUserId || !$hostUserId || !$hostSecret) {
            return new BadRequestResponse();
        }

        // Try to find target game
        /**
         * @var $targetGame HostedGame|null
         */
        $targetGame = HostedGame::query()
            ->where('owner_id = ?', $hostUserId)
            ->andWhere('host_secret = ?', $hostSecret)
            ->querySingleModel();

        if (!$targetGame) {
            return new JsonResponse([
                'result' => 'game_not_found',
                'can_retry' => false
            ]);
        }

        // Try to find announcing player
        /**
         * @var $announcerPlayer HostedGamePlayer|null
         */
        $announcerPlayer = HostedGamePlayer::query()
            ->where('hosted_game_id = ?', $targetGame->id)
            ->andWhere('user_id = ?', $selfUserId)
            ->querySingleModel();

        if (!$announcerPlayer) {
            return new JsonResponse([
                'result' => 'player_not_found',
                'can_retry' => false
            ]);
        }

        // TODO Handle single / multiple announcer scenarios better!

        // End game
        $targetGame->endedAt = new \DateTime('now');
        $targetGame->save();

        return new JsonResponse([
            'result' => 'ok',
            'can_retry' => false
        ]);
    }
}