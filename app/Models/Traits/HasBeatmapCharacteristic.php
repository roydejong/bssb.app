<?php

namespace app\Models\Traits;

use app\BeatSaber\Enums\BeatmapCharacteristic;

trait HasBeatmapCharacteristic
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    /**
     * The raw name of the beatmap characteristic for the current or historic level.
     * Only available in ServerBrowser v1.0+.
     */
    public ?string $characteristic;

    // -----------------------------------------------------------------------------------------------------------------
    // Characteristic

    public function tryParseCharacteristic(): ?BeatmapCharacteristic
    {
        if (!$this->characteristic)
            return null;

        return BeatmapCharacteristic::tryFrom($this->characteristic);
    }

    public function describeCharacteristic(): string
    {
        if (!$this->characteristic)
            return "Unknown";

        return self::tryParseCharacteristic()?->describe() ?? $this->characteristic;
    }

    public function getCharacteristicIcon(): ?string
    {
        if (!$this->characteristic)
            return null;

        return self::tryParseCharacteristic()?->getIconUrl();
    }
}