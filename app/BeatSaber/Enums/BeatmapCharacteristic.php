<?php

namespace app\BeatSaber\Enums;

enum BeatmapCharacteristic : string
{
    case Standard = "Standard";
    case Degrees360 = "360Degree";
    case Degrees90 = "90Degree";
    case NoArrows = "NoArrows";
    case OneColor = "OneSaber";

    public function describe(): string
    {
        return match ($this) {
            self::Standard => "Standard",
            self::Degrees360 => "360 Degrees",
            self::Degrees90 => "90 Degrees",
            self::NoArrows => "No Arrows",
            self::OneColor => "One Saber",
            default => $this->value
        };
    }

    public function getIconUrl(): ?string
    {
        return match ($this) {
            self::Standard => "/static/bsassets/Standard.png",
            self::Degrees360 => "/static/bsassets/360Degrees.png",
            self::Degrees90 => "/static/bsassets/90Degrees.png",
            self::NoArrows => "/static/bsassets/NoArrows.png",
            self::OneColor => "/static/bsassets/OneColor.png",
            default => null
        };
    }
}