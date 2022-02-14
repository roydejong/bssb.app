<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class LevelHistoryPlayer extends Model
{
    public int $id;
    public int $levelHistoryId;
    public int $playerId;
}