<?php

namespace app\AWS;

class GameSessionArn extends Arn
{
    /**
     * The unique identifier for the fleet.
     */
    public ?string $fleetId;

    /**
     * The region code for the fleet instance.
     */
    public ?string $fleetRegion;

    /**
     * The unique identifier for the game session instance.
     */
    public ?string $gameSessionId;
}