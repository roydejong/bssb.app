<?php

namespace app\Controllers\API\V2;

use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerUserId;
use app\External\Steam;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\JsonResponse;
use app\Models\Enums\PlayerType;
use app\Models\Player;

class LoginController
{
    public function login(Request $request): Response
    {
        // -------------------------------------------------------------------------------------------------------------
        // Preflight

        // Must be valid mod client request with JSON POST payload
        if (!$request->getIsValidClientRequest() || !$request->getIsJsonRequest())
            return new BadRequestResponse();

        // Gather basic info
        $json = $request->getJson();
        $userInfo = $json['UserInfo'] ?? null;

        if (!is_array($userInfo))
            return new BadRequestResponse("Invalid user info");

        $modPlatformId = ModPlatformId::fromUserInfoPlatform(intval($userInfo['platform'] ?? null));
        $platformUserId = intval($userInfo['platformUserId'] ?? null);
        $userName = strval($userInfo['userName'] ?? null);

        if ($modPlatformId == ModPlatformId::UNKNOWN)
            return new BadRequestResponse("Invalid platform");

        if (empty($platformUserId) || empty($userName))
            return new BadRequestResponse("Empty user info");

        // -------------------------------------------------------------------------------------------------------------
        // Auth

        $playerValid = false;
        $hasAuthenticated = false;
        $errorMessage = null;

        // Platform auth
        $authTicket = strval($json['AuthenticationToken'] ?? "");
        $steamAuthResult = null;

        if (!empty($authTicket)) {
            if ($modPlatformId == ModPlatformId::STEAM) {
                $steamAuthResult = Steam::tryAuthenticateTicket($authTicket);
                if ($steamAuthResult?->isValid() && $steamAuthResult->steamid == $platformUserId) {
                    // Steam authentication success!
                    $hasAuthenticated = true;
                } else {
                    $errorMessage = "Steam authentication failed";
                }
            } else {
                // TODO Oculus
                // TODO Self-issued token
                $errorMessage = "Unsupported platform for authentication";
            }
        }

        // Check if player profile exists
        $player = Player::tryFromPlatformUserId($modPlatformId, $platformUserId);

        if ($hasAuthenticated && !$player) {
            // If authed, but no player profile exists yet, we can create one
            $player = new Player();
            $player->userId = MultiplayerUserId::hash($modPlatformId, $platformUserId);
            $player->userName = $userName;
            $player->type = PlayerType::PlayerModUser;
            $player->platformType = $modPlatformId;
            $player->platformUserId = $platformUserId;
            $player->firstSeen = new \DateTime('now');
            $player->lastSeen = new \DateTime('now');
            $player->save();
        }

        $avatarUrl = null;

        if ($player && $player->id) {
            $playerValid = true;

            // Update player with any new data
            if (empty($player->platformUserId)) {
                $player->platformType = $modPlatformId;
                $player->platformUserId = $platformUserId;
            }
            if ($hasAuthenticated) {
                $player->lastSeen = new \DateTime('now');
                $player->platformAuthed = true;
                if ($steamAuthResult) {
                    if (!$player->platformOwnershipConfirmed) {
                        $player->platformOwnershipConfirmed = ($steamAuthResult->ownersteamid === $steamAuthResult->steamid);
                    }
                    $steamPlayerSummary = Steam::GetPlayerSummary($platformUserId);
                    if ($steamPlayerSummary) {
                        $avatarUrl = $steamPlayerSummary->avatarfull;
                        $player->platformAvatarUrl = $avatarUrl;
                    }
                }
            }
            $player->save();
        } else {
            $errorMessage = "Not authenticated; player not yet registered";
        }

        // -------------------------------------------------------------------------------------------------------------
        // Response

        return new JsonResponse([
            'success' => $playerValid,
            'authenticated' => $hasAuthenticated,
            'errorMessage' => $errorMessage,
            'avatarUrl' => $avatarUrl
        ]);
    }
}