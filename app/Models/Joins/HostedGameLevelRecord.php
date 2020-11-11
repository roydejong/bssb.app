<?php

namespace app\Models\Joins;

use app\Models\HostedGame;

class HostedGameLevelRecord extends HostedGame
{
    public ?string $beatsaverId;
    public ?string $coverUrl;
    public ?string $levelName;
}