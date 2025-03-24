<?php

namespace app\Models\Enums;

enum PlayerType : string
{
    /**
     * A player that has announced data to the server browser OR that has logged onto the site with Steam.
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
    /**
     * A dedicated server bot used by BeatUpServer instances.
     */
    case DedicatedServerBeatUpServer = 'dedicated_server_beatupserver';
    /**
     * A dedicated server bot used by BeatNet instances.
     */
    case DedicatedServerBeatNetServer = 'dedicated_server_beatnet';
}