<?php

namespace app\Models\Traits;

use app\BeatSaber\Enums\BeatmapCharacteristic;

trait HasBeatmapCharacteristic
{
    /**
     * The raw name of the beatmap characteristic that was played.
     */
    public ?string $characteristic;

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