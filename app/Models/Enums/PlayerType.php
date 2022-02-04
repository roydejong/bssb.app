<?php

namespace app\Models\Enums;

enum PlayerType : string
{
    /**
     * A player that has announced data to the server browser.
     */
    case PlayerModUser = 'player_mod_user';
    /**
     * A player that has been observed by the server browser, but is not known to have used the mod themselves.
     * Their privacy should be guarded more carefully.
     */
    case PlayerObserved = 'player_observed';
    /**
     * A dedicated server bot of an unspecified type, either official or unofficial.
     */
    case DedicatedServer = 'dedicated_server';
    /**
     * A dedicated server bot used by Official GameLift servers.
     */
    case DedicatedServerGameLift = 'dedicated_server_gamelift';
    /**
     * A dedicated server bot used by BeatTogether-based servers.
     */
    case DedicatedServerBeatTogether = 'dedicated_server_beattogether';
}