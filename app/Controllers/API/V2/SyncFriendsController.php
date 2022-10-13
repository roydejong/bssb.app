<?php

namespace app\Controllers\API\V2;

use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerUserId;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\Models\Enums\PlayerType;
use app\Models\Player;
use app\Models\PlayerFriend;

class SyncFriendsController
{
    public function syncFriends(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Input

        if (!$request->getIsValidModClientRequest() || !$request->getIsJsonRequest() || $request->method !== "POST")
            // Request appears to be invalid or not sent from a mod client
            return new BadRequestResponse("what are you");

        $input = $request->getJson();

        $inUserId = strval($input['UserId'] ?? "");
        $inUserName = strval($input['UserName'] ?? "");
        $inPlatform = strval($input['Platform'] ?? "");
        $inPlatformUserId = strval($input['PlatformUserId'] ?? "");
        $inFriends = $input['Friends'] ?? null;

        $modPlatformId = ModPlatformId::normalize($inPlatform);
        $isSteam = $modPlatformId === ModPlatformId::STEAM;
        $isOculus = $modPlatformId === ModPlatformId::OCULUS;

        // -------------------------------------------------------------------------------------------------------------
        // Process

        try {
            $selfPlayer = self::findOrRegisterPlayer($inUserId, $inUserName, $modPlatformId, $inPlatformUserId);
        } catch (\InvalidArgumentException) {
            return new Response(400, "looking real sussy");
        }

        if (($isSteam || $isOculus) && is_array($inFriends)) {
            $curFriends = PlayerFriend::fetchFriendPlayers($selfPlayer, allowPending: true);

            $platformFriends = [];
            foreach ($curFriends as $friendPlayer)
                if ($friendPlayer->platformType == $modPlatformId)
                    $platformFriends[$friendPlayer->id] = $friendPlayer;

            foreach ($inFriends as $friendData) {
                $friendUserName = strval($friendData['UserName'] ?? "");
                $friendPlatformUserId = strval($friendData['PlatformUserId'] ?? "");

                if (!$friendUserName || !$friendPlatformUserId)
                    continue;

                $friendPlayer = self::findOrRegisterPlayer(
                    userId: MultiplayerUserId::hash($modPlatformId, $friendPlatformUserId),
                    userName: $friendUserName,
                    modPlatformId: $modPlatformId,
                    platformUserId: $friendPlatformUserId
                );

                if (!isset($platformFriends[$friendPlayer->id])) {
                    $friendship = PlayerFriend::sendFriendRequest($selfPlayer, $friendPlayer);
                    $friendship->isPending = false;
                    $friendship->save();
                }
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        return new Response(200, "👍 nice friends you have there");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Player handling

    private static function findOrRegisterPlayer(string $userId, string $userName,
                                          string $modPlatformId, string $platformUserId): Player
    {
        if (!$userId || !$userName || !$modPlatformId || !$platformUserId)
            throw new \InvalidArgumentException("Missing data for player lookup/registration");

        if (MultiplayerUserId::hash($modPlatformId, $platformUserId) !== $userId)
            throw new \InvalidArgumentException("Inconsistent user id given the platform identifiers");

        /**
         * @var $player Player|null
         */
        $player = Player::query()
            ->where('user_id = ?', $userId)
            ->querySingleModel();

        if (!$player) {
            // Register new player
            $player = new Player();
            $player->userId = $userId;
            $player->userName = $userName;
            $player->type = PlayerType::PlayerModUser;
            $player->firstSeen = new \DateTime('now');
        } else {
            // Update existing player
            $player->userName = $userName;
            if ($player->type === PlayerType::PlayerObserved) {
                $player->type = PlayerType::PlayerModUser;
            }
        }

        $player->platformType = $modPlatformId;
        $player->platformUserId = $platformUserId;
        $player->lastSeen = new \DateTime('now');
        $player->save();

        return $player;
    }
}