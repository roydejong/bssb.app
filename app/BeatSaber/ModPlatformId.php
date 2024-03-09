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
     * Oculus PC or Oculus Quest build of the game.
     */
    public const OCULUS = "oculus";
    /**
     * A PlayStation no one will ever see here.
     */
    public const PS4 = "ps4";
    /**
     * Dedicated server.
     */
    public const DEDI = "dedi";

    public static function normalize(?string $input): string
    {
        if (empty($input)) {
            return self::UNKNOWN;
        }

        $input = strtolower($input);

        if (str_starts_with($input, "oculus")) {
            // MultiplayerCore uses "OculusPC" and "OculusQuest", normalize to Oculus
            return self::OCULUS;
        }

        return $input;
    }

    public static function fromUserInfoPlatform(mixed $input): string
    {
        // Game enum: Test = 0, Steam = 1, Oculus = 2, PS4 = 3, PS5 = 4
        switch (intval($input))
        {
            case 1:
                return self::STEAM;
            case 2:
                return self::OCULUS;
            default:
                return self::UNKNOWN;
        }
    }
}