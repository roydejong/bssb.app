<?php

namespace app\Models;

use app\BeatSaber\ModPlatformId;
use app\Utils\PirateDetect;
use app\Utils\PlayerBotDetect;
use SoftwarePunt\Instarecord\Model;

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
    public bool $isAnnouncer;
    public float $latency;
    public bool $isConnected;

    // -----------------------------------------------------------------------------------------------------------------
    // Data helpers

    public function describeLatency(): string
    {
        if ($this->latency <= 0)
            return "?ms";
        return ($this->latency * 1000) . "ms";
    }

    public function getIsPirate(): bool
    {
        return PirateDetect::detect($this->userId, $this->userName);
    }

    public function getIsBot(): bool
    {
        if ($this->sortIndex < 0 && $this->userName === "Dedicated Server")
            return true;

        return PlayerBotDetect::detect($this->userId, $this->userName);
    }

    public function getUrlSafeUserId(): string
    {
        return Player::cleanUserIdForUrl($this->userId);
    }

    public function getProfileUrl(): string
    {
        return "/player/{$this->getUrlSafeUserId()}";
    }

    public function getIsDedicatedServer(): bool
    {
        return $this->isHost && $this->sortIndex === -1;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Player connection

    public function syncProfileData(?string $platformType, ?string $platformUserId, ?array $avatarData): Player
    {
        $player = Player::fromServerPlayer($this);

        if (!empty($platformType) && $platformType !== "unknown" && intval($platformType) !== 0)
            $player->platformType = ModPlatformId::normalize($platformType);

        if ($platformUserId)
            $player->platformUserId = $platformUserId;

        if ($avatarData)
            $player->syncAvatarData($avatarData);

        $player->save();
        return $player;
    }
}