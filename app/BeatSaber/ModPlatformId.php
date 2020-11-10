<?php

namespace app\BeatSaber;

/**
 * Enum: platform ids used by the mod.
 */
class ModPlatformId
{
    /**
     * The default platform identifier, used when a version could not be determined.
     */
    public const UNKNOWN = "unknown";
    /**
     * Steam PC build of the game.
     */
    public const STEAM = "steam";
    /**
     * Oculus PC build of the game.
     */
    public const OCULUS = "oculus";
}