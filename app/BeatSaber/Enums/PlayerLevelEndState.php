<?php

namespace app\BeatSaber\Enums;

enum PlayerLevelEndState : int
{
    case SongFinished = 0;
    case NotFinished = 1;
    case NotStarted = 2;

    public function describe(): string
    {
        return match($this) {
            PlayerLevelEndState::NotStarted => "Not started",
            PlayerLevelEndState::NotFinished => "Not finished",
            PlayerLevelEndState::SongFinished => "Finished level",
        };
    }
}