<?php

namespace app\Models;

use app\Utils\PirateDetect;
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

    public function getIsPirate(): bool
    {
        return PirateDetect::detect($this->userId, $this->userName);
    }
}