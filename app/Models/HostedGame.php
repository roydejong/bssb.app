<?php

namespace app\Models;

use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\BSSB;
use app\Common\CVersion;
use app\Utils\PirateDetect;
use Hashids\Hashids;
use SoftwarePunt\Instarecord\Model;

class HostedGame extends Model implements \JsonSerializable
{
    // -----------------------------------------------------------------------------------------------------------------
    // Consts

    const MAX_PLAYER_LIMIT_VANILLA = 5;
    const MAX_PLAYER_LIMIT_MODDED = 100;

    public const SERVER_TYPE_PLAYER_HOST = "player_host";
    public const SERVER_TYPE_BEATDEDI_CUSTOM = "beatdedi_custom";
    public const SERVER_TYPE_BEATDEDI_QUICKPLAY = "beatdedi_quickplay";
    public const SERVER_TYPE_VANILLA_QUICKPLAY = "vanilla_quickplay";

    // -----------------------------------------------------------------------------------------------------------------
    // Columns

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
    public ?\DateTime $endedAt;
    public ?string $mpExVersion;
    public ?CVersion $modVersion;
    public ?CVersion $gameVersion;
    public ?string $serverType;
    public ?string $hostSecret;

    // -----------------------------------------------------------------------------------------------------------------
    // Relationships

    /**
     * @return HostedGamePlayer[]
     */
    public function fetchPlayers(): array
    {
        if ($this->id > 0) {
            return HostedGamePlayer::query()
                ->where('hosted_game_id = ?', $this->id)
                ->orderBy('sort_index ASC')
                ->queryAllModels();
        }
        return [];
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Lifecycle

    /**
     * The amount of minutes before a game is considered "stale".
     *  → Stale games should not be shown on the site, should not be visible on the API, and should be archived.
     */
    public const STALE_GAME_AFTER_MINUTES = 5;

    public function getIsStale(): bool
    {
        return $this->lastUpdate < self::getStaleGameCutoff();
    }

    public static function getStaleGameCutoff(): \DateTime
    {
        $cutoffMinutes = self::STALE_GAME_AFTER_MINUTES;

        return (new \DateTime('now'))
            ->modify("-{$cutoffMinutes} minutes");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Data helpers

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

    public function describeDifficulty(): string
    {
        return LevelDifficulty::describe($this->difficulty);
    }

    public function describeState(): string
    {
        return MultiplayerLobbyState::describe($this->lobbyState);
    }

    public function describeServerType(): string
    {
        switch ($this->serverType) {
            default:
            case null:
            case self::SERVER_TYPE_PLAYER_HOST:
                return "Player hosted";
            case self::SERVER_TYPE_VANILLA_QUICKPLAY:
                return "Official Quickplay";
            case self::SERVER_TYPE_BEATDEDI_QUICKPLAY:
                return "BeatDedi Quickplay";
            case self::SERVER_TYPE_BEATDEDI_CUSTOM:
                return "BeatDedi Custom";
        }
    }

    public function getMinPlayerCount(): int
    {
        if ($this->getIsBeatDedi()) {
            // BeatDedi may report a zero player count
            return 0;
        }

        return 1;
    }

    public function getMaxPlayerLimit(): int
    {
        if (!$this->getIsOfficial() && $this->isModded) {
            // Higher player limit is only possible for modded games on unofficial servers
            return self::MAX_PLAYER_LIMIT_MODDED;
        }
        return self::MAX_PLAYER_LIMIT_VANILLA;
    }

    public function getIsPirate(): bool
    {
        return PirateDetect::detect($this->ownerId, $this->ownerName);
    }

    public function getIsPlayerHost(): bool
    {
        return empty($this->serverType) || $this->serverType === self::SERVER_TYPE_PLAYER_HOST;
    }

    public function getIsBeatDedi(): bool
    {
        return $this->serverType === self::SERVER_TYPE_BEATDEDI_CUSTOM ||
            $this->serverType === self::SERVER_TYPE_BEATDEDI_QUICKPLAY;
    }

    public function getIsQuickplay(): bool
    {
        return $this->serverType === self::SERVER_TYPE_BEATDEDI_QUICKPLAY ||
            $this->serverType === self::SERVER_TYPE_VANILLA_QUICKPLAY;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Moderation

    public function getIsUninteresting(): bool
    {
        if ($this->masterServerHost === "127.0.0.1" || $this->masterServerHost === "localhost") {
            return true;
        }
        return false;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // URLs

    private static function getHashids(): Hashids
    {
        return BSSB::getHashids("HostedGame");
    }

    public static function hash2id(string $hash): ?int
    {
        $arr = self::getHashids()->decode($hash);
        return !empty($arr) ? intval($arr[0]) : null;
    }

    public static function id2hash(int $id): string
    {
        return self::getHashids()->encode($id);
    }

    public function getWebDetailUrl(): string
    {
        $hash = self::id2hash($this->id);
        return "/game/{$hash}";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function jsonSerialize()
    {
        $sz = $this->getPropertyValues();
        unset($sz['ownerId']);
        return $sz;
    }
}