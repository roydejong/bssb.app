<?php

namespace app\BeatSaber;

use app\Common\CVersion;

/**
 * Information about a modded client, derived from its user agent string.
 */
class ModClientInfo
{
    const MOD_SERVER_BROWSER_PC = "ServerBrowser";
    const MOD_SERVER_BROWSER_QUEST = "ServerBrowserQuest";
    const MOD_BEATDEDI = "BeatDedi";

    /**
     * The name of the mod.
     * This should always be "ServerBrowser", "ServerBrowserQuest", or "BeatDedi".
     *
     * @see "MOD_*" constants
     */
    public ?string $modName = null;

    /**
     * The full assembly version of the mod.
     * This should be in the format "X.Y.Z.0".
     */
    public ?CVersion $assemblyVersion = null;

    /**
     * The Beat Saber version of the mod.
     */
    public ?CVersion $beatSaberVersion = null;

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
            $assemblyVersion = $modSubParts[1] ?? null;

            if ($assemblyVersion) {
                $result->assemblyVersion = new CVersion($assemblyVersion);
            }
        }

        if ($beatSaberPart) {
            $result->beatSaberVersion = new CVersion(strtok(trim($beatSaberPart, '()'), 'BeatSaber/'));
        }

        if ($platformPart) {
            $result->platformId = trim($platformPart, '()');
        }

        return $result;
    }
}