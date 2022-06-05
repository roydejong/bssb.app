<?php

namespace app\BeatSaber\Enums;

enum GameplayEnabledObstacleType : int
{
    case All = 0;
    case FullHeightOnly = 1;
    case NoObstacles = 2;
}