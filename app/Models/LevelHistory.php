<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class LevelHistory extends Model
{
    public int $id;
    public int $hostedGameId;
    public int $levelId;
}