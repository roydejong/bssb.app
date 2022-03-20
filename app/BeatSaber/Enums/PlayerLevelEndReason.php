<?php

namespace app\BeatSaber\Enums;

enum PlayerLevelEndReason : int
{
    case Cleared = 0;
    case Failed = 1;
    case GivenUp = 2;
    case Quit = 3;
    case HostEndedLevel = 4;
    case WasInactive = 5;
    case StartupFailed = 6;
    case ConnectedAfterLevelEnded = 7;

    public function describe(): string
    {
        return match ($this) {
            PlayerLevelEndReason::ConnectedAfterLevelEnded => "Connected late",
            PlayerLevelEndReason::GivenUp => "Gave up",
            PlayerLevelEndReason::HostEndedLevel => "Host ended",
            PlayerLevelEndReason::Quit => "Player quit",
            PlayerLevelEndReason::StartupFailed => "Startup failed",
            PlayerLevelEndReason::WasInactive => "Player inactive",
            PlayerLevelEndReason::Failed => "Level failed",
            PlayerLevelEndReason::Cleared => "Level cleared"
        };
    }
}