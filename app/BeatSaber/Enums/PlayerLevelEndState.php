<?php

namespace app\BeatSaber\Enums;

enum PlayerLevelEndState : int
{
    case SongFinished = 0;
    case NotFinished = 1;
    case NotStarted = 2;
}