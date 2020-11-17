<?php

namespace app\BeatSaber;

class LevelDifficulty
{
    const Easy = 0;
    const Normal = 1;
    const Hard = 2;
    const Expert = 3;
    const ExpertPlus = 4;

    public static function describe(?int $difficulty): string
    {
        switch ($difficulty) {
            default: return "Unknown";
            case self::Easy: return "Easy";
            case self::Normal: return "Normal";
            case self::Hard: return "Hard";
            case self::Expert: return "Expert";
            case self::ExpertPlus: return "Expert+";
        }
    }
}