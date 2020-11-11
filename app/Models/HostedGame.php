<?php

namespace app\Models;

use app\BeatSaber\ModPlatformId;
use Instasell\Instarecord\Model;

class HostedGame extends Model
{
    public int $id;
    public string $serverCode;
    public string $gameName;
    public string $ownerId;
    public string $ownerName;
    public int $playerCount;
    public int $playerLimit;
    public bool $isModded;
    public \DateTime $firstSeen;
    public \DateTime $lastUpdate;
    public int $lobbyState;
    public ?string $levelId;
    public ?string $songName;
    public ?string $songAuthor;
    public ?int $difficulty;
    public string $platform = ModPlatformId::UNKNOWN;
    public ?string $masterServerHost;
    public ?int $masterServerPort;

    public function getIsOfficial(): bool
    {
        return $this->masterServerHost === null
            || strpos($this->masterServerHost, ".mp.beatsaber.com") !== false;
    }

    public function describeMasterServer(): string
    {
        if ($this->getIsOfficial()) {
            return "Official";
        } else if ($this->masterServerHost) {
            return $this->masterServerHost;
        } else {
            return "Unknown";
        }
    }

    public function describeSong(): string
    {
        $parts = [];
        if ($this->songAuthor)
            $parts[] = $this->songAuthor;
        if ($this->songName)
            $parts[] = $this->songName;
        return implode(' - ', $parts);
    }
}