<?php

namespace app\Models;

use app\Models\Traits\HasLevelHistoryData;
use SoftwarePunt\Instarecord\Model;

class LevelHistory extends Model
{
    use HasLevelHistoryData;

    public int $id;
}