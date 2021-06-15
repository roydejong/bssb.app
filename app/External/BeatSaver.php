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

    public static function downloadCoverArt(string $cdnUrl): ?string
    {
        if (!str_starts_with($cdnUrl, "https://beatsaver.com/cdn/")) {
            return null;
        }

        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: BeatSaberServerBrowser API (https://bssb.app)\r\n"
            ]
        ]);

        $rawCoverData = file_get_contents($cdnUrl, false, $context);

        if (!$rawCoverData) {
            return null;
        }

        $baseName = basename($cdnUrl);

        $relativePath = "/static/saver/{$baseName}";
        $storagePath = DIR_PUBLIC . $relativePath;

        if (@file_put_contents($storagePath, $rawCoverData)) {
            return $relativePath;
        }

        return null;
    }
}