<?php

namespace app\Models;

use Instasell\Instarecord\Model;

class HostedGamePlayer extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    public int $id;
    public int $hostedGameId;
    public int $sortIndex;
    public string $userId;
    public string $userName;
    public bool $isHost;
    public float $latency;
    public bool $isConnected;

    // -----------------------------------------------------------------------------------------------------------------
    // Data helpers

    public function describeLatency(): string
    {
        return ($this->latency * 1000) . "ms";
    }
}