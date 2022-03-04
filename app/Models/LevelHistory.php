<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class LevelHistory extends Model
{
    public int $id;
    public int $hostedGameId;
    public int $levelRecordId;
    /**
     * The server-assigned GUID for the specific level play.
     */
    public string $sessionGameId;
    /**
     * @var \DateTime
     */
    public \DateTime $startedAt;
    public ?\DateTime $endedAt;
}