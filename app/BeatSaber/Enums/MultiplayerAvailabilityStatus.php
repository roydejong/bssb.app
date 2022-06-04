<?php

namespace app\BeatSaber\Enums;

enum MultiplayerAvailabilityStatus : int
{
    case Online = 0;
    case MaintenanceUpcoming = 1;
    case Offline = 2;

    public function describe(): string
    {
        return match ($this) {
            self::Online => "Online",
            self::MaintenanceUpcoming => "Maintenance upcoming",
            self::Offline => "Offline",
            default => $this->value
        };
    }
}