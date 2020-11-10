<?php

namespace app\BeatSaber;

/**
 * Information about a modded client, derived from its user agent string.
 */
class ModClientInfo
{
    /**
     * The name of the mod.
     * This should always be "ServerBrowser".
     */
    public ?string $modName = null;
    /**
     * The full assembly version of the mod.
     * This should be in the format "X.Y.Z.0".
     */
    public ?string $assemblyVersion = null;
    /**
     * The Beat Saber version of the mod.
     */
    public ?string $beatSaberVersion = null;
    /**
     * The platform identifier of the mod.
     * @see ModPlatformId
     */
    public string $platformId = ModPlatformId::UNKNOWN;

    public static function fromUserAgent(string $userAgent): ModClientInfo
    {
        $result = new ModClientInfo();

        $parts = explode(' ', $userAgent);
        $modPart = $parts[0] ?? null;
        $beatSaberPart = $parts[1] ?? null;
        $platformPart = $parts[2] ?? null;

        if ($modPart) {
            $modSubParts = explode('/', $modPart);

            $result->modName = $modSubParts[0] ?? null;
            $result->assemblyVersion = $modSubParts[1] ?? null;
        }

        if ($beatSaberPart) {
            $result->beatSaberVersion = strtok(trim($beatSaberPart, '()'), 'BeatSaber/');
        }

        if ($platformPart) {
            $result->platformId = trim($platformPart, '()');
        }

        return $result;
    }
}