<?php

namespace app\BeatSaber;

class MultiplayerLobbyState
{
    public const None = 0;
    public const LobbySetup = 1;
    public const GameStarting = 2;
    public const GameRunning = 3;
    public const Error = 4;

    public static function describe(?int $state): string
    {
        switch ($state) {
            default: return "Unknown";
            case self::None: return "None";
            case self::LobbySetup: return "In lobby";
            case self::GameStarting: return "Level starting";
            case self::GameRunning: return "Playing level";
            case self::Error: return "Error";
        }
    }
}