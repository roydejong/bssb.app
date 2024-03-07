<?php

namespace app\External;

use app\External\Models\SteamAuthenticateUserTicketResponse;

class Steam
{
    /**
     * Tries to authenticate a user's ticket with the Steam Web API.
     * Will return the user's Steam ID if successful, or null if not.
     */
    public static function tryAuthenticateTicket(string $ticket): ?SteamAuthenticateUserTicketResponse
    {
        $response = self::makeApiRequest("/ISteamUserAuth/AuthenticateUserTicket/v1", [
            "appid" => 620980,
            "ticket" => $ticket
        ]);

        // Example response:
        // {
        //   "response":{
        //      "params":{
        //         "result":"OK",
        //         "steamid":"76561198002398493",
        //         "ownersteamid":"76561198002398493",
        //         "vacbanned":false,
        //         "publisherbanned":false
        //      }
        //   }
        //}

        if ($response === null || !isset($response["response"]["params"]))
            return null;

        return new SteamAuthenticateUserTicketResponse($response["response"]["params"]);
    }

    public static function makeApiRequest(string $path, array $params): ?array
    {
        global $bssbConfig;
        $steamWebApiKey = $bssbConfig["steam_web_api_key"] ?? null;

        $path = rtrim($path, "/");
        $targetUrl = "https://api.steampowered.com{$path}/"
            . "?key={$steamWebApiKey}&" . http_build_query($params);

        $rawResponse = @file_get_contents($targetUrl);

        if ($rawResponse === false)
            return null;

        return json_decode($rawResponse, true);
    }
}