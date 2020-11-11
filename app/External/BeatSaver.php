<?php

namespace app\External;

use app\HTTP\Request;

class BeatSaver
{
    public static function fetchMapDataByHash(string $hash): ?array
    {
        $request = new Request();
        $request->protocol = "https";
        $request->host = "beatsaver.com";
        $request->path = "/api/maps/by-hash/{$hash}";
        $request->headers['User-Agent'] = "BeatSaberServerBrowser API (https://bssb.app)";

        try {
            $response = $request->send();

            if ($response->code === 200) {
                $result = @json_decode($response->body, true);

                if ($result) {
                    return $result;
                }
            }
        } catch (\Exception $ex) {
            // 🙁
        }

        return null;
    }
}