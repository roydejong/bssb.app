<?php

namespace app\BeatSaber\Enums;

enum GameplayModifier: string
{
    case DisappearingArrows = "MODIFIER_DISAPPEARING_ARROWS";
    case FasterSong = "MODIFIER_FASTER_SONG";
    case FourLives = "MODIFIER_FOUR_LIVES";
    case NoArrows = "MODIFIER_NO_ARROWS";
    case NoBombs = "MODIFIER_NO_BOMBS";
    case NoObstacles = "MODIFIER_NO_OBSTACLES";
    case OneLife = "MODIFIER_ONE_LIFE";
    case ProMode = "MODIFIER_PRO_MODE";
    case SlowerSong = "MODIFIER_SLOWER_SONG";
    case SmallCubes = "MODIFIER_SMALL_CUBES";
    case StrictAngles = "MODIFIER_STRICT_ANGLES";
    case SuperFastNotes = "MODIFIER_SUPER_FAST_NOTES";
    case SuperFastSong = "MODIFIER_SUPER_FAST_SONG";
    case ZenMode ="MODIFIER_ZEN_MODE";
}