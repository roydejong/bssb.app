<?php

namespace app\BeatSaber\Enums;

enum GameplaySongSpeed : int
{
    case Normal = 0;
    case Faster = 1;
    case Slower = 2;
    case SuperFast = 3;
}