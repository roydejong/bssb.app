<?php

namespace app\Models;

use app\Models\Joins\LevelHistoryPlayerWithPlayerDetails;
use app\Models\Traits\HasLevelHistoryData;
use SoftwarePunt\Instarecord\Model;

class LevelHistory extends Model implements \JsonSerializable
{
    use HasLevelHistoryData;

    public int $id;

    // -----------------------------------------------------------------------------------------------------------------
    // Relationships

    /**
     * @return LevelHistoryPlayerWithPlayerDetails[]
     */
    public function fetchPlayerResults(): array
    {
        return LevelHistoryPlayerWithPlayerDetails::fetchForLevelHistory($this->id);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialization

    public function jsonSerialize(): mixed
    {
        $sz = $this->getPropertyValues();
        unset($sz['id']);
        unset($sz['modifiers']);
        unset($sz['hostedGameId']);
        unset($sz['levelRecordId']);
        unset($sz['statPlayCount']);
        unset($sz['description']);
        unset($sz['duration']);
        return $sz;
    }
}