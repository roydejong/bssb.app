<?php

namespace app\BeatSaber\Enums;

enum MultiplayerBadge : string
{
    case ComboMax = "BADGE_MAX_COMBO";
    case ComboMin = "BADGE_MIN_COMBO";
    case FullCombo = "BADGE_FULL_COMBO";
    case GoodCutsMax = "BADGE_MAX_GOOD_CUTS";
    case GoodCutsMin = "BADGE_MIN_GOOD_CUTS";
    case HandMovementMax = "BADGE_MAX_HAND_MOVEMENT";
    case HandMovementMin = "BADGE_MIN_HAND_MOVEMENT";
    case PrecisionMax = "BADGE_MAX_PRECISION";
    case PrecisionMin = "BADGE_MIN_PRECISION";
    case SaberMovementMax = "BADGE_MAX_SABER_MOVEMENT";
    case SaberMovementMin = "BADGE_MIN_SABER_MOVEMENT";

    public function describe(): string
    {
        return match ($this) {
            self::ComboMax => "Longest Journey",
            self::ComboMin => "Casual Cutter",
            self::FullCombo => "Perfectionist",
            self::GoodCutsMax => "No Mercy",
            self::GoodCutsMin => "Air Slicer",
            self::HandMovementMax => "Jazz Hands",
            self::HandMovementMin => "Fencer",
            self::PrecisionMax => "Saber Surgeon",
            self::PrecisionMin => "Lumberjack",
            self::SaberMovementMax => "Restless Slicer",
            self::SaberMovementMin => "Nihilist",
            default => $this->name
        };
    }

    public function getIconUrl(): ?string
    {
        return match ($this) {
            self::ComboMax => "/static/bsassets/BestComboBadge.png",
            self::ComboMin => "/static/bsassets/WorstComboBadge.png",
            self::FullCombo => "/static/bsassets/FullComboBadge.png",
            self::GoodCutsMax => "/static/bsassets/MostGoodCutsBadge.png",
            self::GoodCutsMin => "/static/bsassets/LeastGoodCutsBadge.png",
            self::HandMovementMax => "/static/bsassets/MostHandMovementBadge.png",
            self::HandMovementMin => "/static/bsassets/LeastHandMovementBadge.png",
            self::PrecisionMax => "/static/bsassets/MostPreciseBadge.png",
            self::PrecisionMin => "/static/bsassets/LeastPreciseBadge.png",
            self::SaberMovementMax => "/static/bsassets/MostSaberMovementBadge.png",
            self::SaberMovementMin => "/static/bsassets/LeastSaberMovementBadge.png",
            default => null
        };
    }
}