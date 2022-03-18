<?php

namespace app\BeatSaber\Enums;

enum GameplayModifier: string
{
    case DisappearingArrows = "MODIFIER_DISAPPEARING_ARROWS";
    case FasterSong = "MODIFIER_FASTER_SONG";
    case FourLives = "MODIFIER_FOUR_LIVES";
    case NoArrows = "MODIFIER_NO_ARROWS";
    case NoBombs = "MODIFIER_NO_BOMBS";
    case NoFailOn0Energy = "MODIFIER_NO_FAIL_ON_0_ENERGY";
    case NoObstacles = "MODIFIER_NO_OBSTACLES";
    case OneLife = "MODIFIER_ONE_LIFE";
    case ProMode = "MODIFIER_PRO_MODE";
    case SlowerSong = "MODIFIER_SLOWER_SONG";
    case SmallCubes = "MODIFIER_SMALL_CUBES";
    case StrictAngles = "MODIFIER_STRICT_ANGLES";
    case SuperFastNotes = "MODIFIER_SUPER_FAST_NOTES";
    case SuperFastSong = "MODIFIER_SUPER_FAST_SONG";
    case ZenMode = "MODIFIER_ZEN_MODE";
    case GhostNotes = "MODIFIER_GHOST_NOTES";

    public function describe(): string
    {
        return match ($this) {
            self::DisappearingArrows => "Disappearing Arrows",
            self::FasterSong => "Faster Song",
            self::FourLives => "4 Lives",
            self::NoArrows => "No Arrows",
            self::NoBombs => "No Bombs",
            self::NoFailOn0Energy => "No Fail",
            self::NoObstacles => "No Walls",
            self::OneLife => "1 Life",
            self::ProMode => "Pro Mode",
            self::SlowerSong => "Slower Song",
            self::SmallCubes => "Small Notes",
            self::StrictAngles => "Strict Angles",
            self::SuperFastNotes => "Fast Notes(?)",
            self::SuperFastSong => "Super Fast Song",
            self::ZenMode => "Zen Mode",
            self::GhostNotes => "Ghost Notes",
            default => $this->value
        };
    }

    public function getIconUrl(): ?string
    {
        return match ($this) {
            self::DisappearingArrows => "/static/bsassets/DisappearingArrows.png",
            self::FasterSong => "/static/bsassets/FasterSongIcon.png",
            self::FourLives => "/static/bsassets/FourLivesIcon.png",
            self::NoArrows => "/static/bsassets/NoArrowsIcon.png",
            self::NoBombs => "/static/bsassets/NoBombsIcon.png",
            self::NoFailOn0Energy => "/static/bsassets/NoFailIcon.png",
            self::NoObstacles => "/static/bsassets/NoObstaclesIcon.png",
            self::OneLife => "/static/bsassets/OneLifeIcon.png",
            self::ProMode => "/static/bsassets/ProModeIcon.png",
            self::SlowerSong => "/static/bsassets/SlowerSongIcon.png",
            self::SmallCubes => "/static/bsassets/SmallNotesIcon.png",
            self::StrictAngles => "/static/bsassets/PreciseAnglesIcon.png",
            self::SuperFastNotes => "/static/bsassets/FastNotesIcon.png",
            self::SuperFastSong => "/static/bsassets/SuperFastSongIcon.png",
            self::ZenMode => "/static/bsassets/ZenIcon.png",
            self::GhostNotes => "/static/bsassets/GhostNotes.png",
            default => null
        };
    }
}