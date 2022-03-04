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
}