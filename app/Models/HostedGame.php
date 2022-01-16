<?php

namespace app\Models;

use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\BSSB;
use app\Common\CVersion;
use app\Common\IPEndPoint;
use app\Utils\PirateDetect;
use DateTime;
use Hashids\Hashids;
use SoftwarePunt\Instarecord\Model;

class HostedGame extends Model implements \JsonSerializable
{
    // -----------------------------------------------------------------------------------------------------------------
    // Consts

    const MAX_PLAYER_LIMIT_VANILLA = 5;
    const MAX_PLAYER_LIMIT_MODDED = 100;

    public const SERVER_TYPE_BEATTOGETHER_DEDICATED = "beattogether_dedicated";
    public const SERVER_TYPE_BEATTOGETHER_QUICKPLAY = "beattogether_quickplay";
    public const SERVER_TYPE_BEATDEDI_CUSTOM = "beatdedi_custom";
    public const SERVER_TYPE_BEATDEDI_QUICKPLAY = "beatdedi_quickplay";
    public const SERVER_TYPE_NORMAL_DEDICATED = "vanilla_dedicated";
    public const SERVER_TYPE_NORMAL_QUICKPLAY = "vanilla_quickplay";
    public const SERVER_TYPE_PLAYER_HOST = "player_host";

    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    /**
     * Internal database ID.
     * Should not be publicly disclosed, use Hash IDs instead.
     */
    public int $id;
    /**
     * The server code that can be used to join the game. Is unique per master server.
     * Can only be used for managed games (servers created by players, not Quick Play), but is always set.
     */
    public string $serverCode;
    /**
     * The name of the lobby, as set by the host or party leader.
     */
    public string $gameName;
    /**
     * The User ID of the host player / server.
     * This seems to be randomized for dedicated servers.
     */
    public string $ownerId;
    /**
     * The username of the host player / server.
     * This is empty for dedicated servers.
     */
    public string $ownerName;
    /**
     * Current amount of players.
     */
    public int $playerCount;
    /**
     * Maximum amount of players.
     * Limited to 5 for official games, may be higher on modded games.
     */
    public int $playerLimit;
    /**
     * Indicates if this is a modded lobby with custom song support.
     */
    public bool $isModded;
    /**
     * First time this ownerId was seen.
     */
    public \DateTime $firstSeen;
    /**
     * Last time an announcement was received for this lobby.
     */
    public \DateTime $lastUpdate;
    /**
     * Lobby state enum value.
     * @see MultiplayerLobbyState
     *
     * Value should be interpreted different based on the game version used.
     * @see getAdjustedState()
     */
    public int $lobbyState;
    /**
     * Current/last song, level id.
     */
    public ?string $levelId;
    /**
     * Current/last song, song name.
     */
    public ?string $songName;
    /**
     * Current/last song, author name.
     */
    public ?string $songAuthor;
    /**
     * Difficulty of the current or most recent song, or the locked difficulty of the lobby (for Quick Play).
     */
    public ?int $difficulty;
    /**
     * Characteristic of the level being played.
     * Only available in ServerBrowser v1.0+.
     */
    public ?string $characteristic;
    /**
     * Platform identifier indicating cross-play compatibility.
     */
    public string $platform = ModPlatformId::UNKNOWN;
    /**
     * Host name for the Master Server this game is hosted on.
     */
    public ?string $masterServerHost;
    /**
     * Port number for the Master Server this game is hosted on.
     */
    public ?int $masterServerPort;
    /**
     * Indicates when the announcement was cancelled, or NULL if it was not explicitly cancelled.
     */
    public ?\DateTime $endedAt;
    /**
     * The version of the MultiplayerExtensions mod the announcer has.
     * Used to verify compatibility; only clients with the exact same version should join.
     */
    public ?string $mpExVersion;
    /**
     * The name of the mod or software used to send the announcement.
     * Examples: "ServerBrowser", "ServerBrowserQuest", "BeatDedi"
     */
    public string $modName = "ServerBrowser";
    /**
     * The version of the mod or software used to send the announcement.
     * Relative to $modName.
     */
    public ?CVersion $modVersion;
    /**
     * The announcer's game version, extracted from the User Agent header.
     */
    public ?CVersion $gameVersion;
    /**
     * Indicates what kind of server this is, affects how the connection is established.
     * @see HostedGame::SERVER_TYPE_*
     */
    public ?string $serverType;
    /**
     * The host's secret. Used to connect to specific dedicated server instances.
     */
    public ?string $hostSecret;
    /**
     * The actual server endpoint the client connected to. Used for direct connections.
     */
    public ?IPEndPoint $endpoint;
    /**
     * The User ID of the current party leader, the player in control of the server.
     */
    public ?string $managerId;

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

    public function getIsDirectConnect(): bool
    {
        return empty($this->masterServerHost) && !empty($this->endpoint);
    }

    public function getIsOfficial(): bool
    {
        if ($this->getIsDirectConnect())
            return false;

        return $this->masterServerHost === null
            || strpos($this->masterServerHost, ".mp.beatsaber.com") !== false;
    }

    public function describeMasterServer(): string
    {
        if ($this->getIsDirectConnect())
            return "Direct Connection";

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

        if (empty($parts))
            return $this->levelId;

        return implode(' - ', $parts);
    }

    public function describeDifficulty(): string
    {
        return LevelDifficulty::describe($this->difficulty);
    }

    public function getAdjustedState(?CVersion $observerGameVersion = null): int
    {
        $state = $this->lobbyState;

        $thresholdVersion = new CVersion("1.16.3");

        $senderIsOutdated = !$this->gameVersion || $this->gameVersion->lessThan($thresholdVersion);
        $observerIsOutdated = $observerGameVersion && $observerGameVersion->lessThan($thresholdVersion);

        if ($senderIsOutdated && !$observerIsOutdated) {
            // Game created on 1.16.2 or lower, the "LobbyCountdown" state does not yet exist
            // The observer is on 1.16.3 or newer, so adjust the state up
            if ($state >= MultiplayerLobbyState::LobbyCountdown) {
                $state++;
            }
        } else if (!$senderIsOutdated && $observerIsOutdated) {
            // Observer is on 1.16.2 or older, and does not yet know the "LobbyCountdown" state
            // Game was created on 1.16.3 or newer, so adjust the state down
            if ($state >= MultiplayerLobbyState::LobbyCountdown) {
                $state--;
            }
        }

        return $state;
    }

    public function describeState(): string
    {
        return MultiplayerLobbyState::describe($this->getAdjustedState());
    }

    public function describeServerType(): string
    {
        $isOfficial = $this->getIsOfficial();

        return match ($this->serverType) {
            self::SERVER_TYPE_BEATTOGETHER_DEDICATED => "BeatTogether Dedicated",
            self::SERVER_TYPE_BEATTOGETHER_QUICKPLAY => "BeatTogether Quickplay",
            self::SERVER_TYPE_BEATDEDI_CUSTOM => "BeatDedi Custom",
            self::SERVER_TYPE_BEATDEDI_QUICKPLAY => "BeatDedi Quickplay",
            self::SERVER_TYPE_NORMAL_QUICKPLAY => ($isOfficial ? "Official Quickplay" : "Unofficial Quickplay"),
            self::SERVER_TYPE_NORMAL_DEDICATED => ($isOfficial ? "Official Dedicated" : "Unofficial Dedicated"),
            null, self::SERVER_TYPE_PLAYER_HOST => "Player hosted (Old P2P)",
            default => "Unknown"
        };
    }

    public function getIsInLobby(): bool
    {
        $stateNormal = $this->getAdjustedState();

        return $stateNormal === MultiplayerLobbyState::None ||
            $stateNormal === MultiplayerLobbyState::LobbySetup ||
            $stateNormal === MultiplayerLobbyState::LobbyCountdown ||
            $stateNormal === MultiplayerLobbyState::Error;
    }

    public function getIsPlayingLevel(): bool
    {
        return !empty($this->levelId) && !$this->getIsInLobby();
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

    public function getIsBeatTogether(): bool
    {
        return $this->serverType === self::SERVER_TYPE_BEATTOGETHER_DEDICATED ||
            $this->serverType === self::SERVER_TYPE_BEATTOGETHER_QUICKPLAY;
    }

    public function getIsBeatDedi(): bool
    {
        return $this->serverType === self::SERVER_TYPE_BEATDEDI_CUSTOM ||
            $this->serverType === self::SERVER_TYPE_BEATDEDI_QUICKPLAY;
    }

    public function getIsQuickplay(): bool
    {
        return $this->serverType === self::SERVER_TYPE_BEATTOGETHER_QUICKPLAY ||
            $this->serverType === self::SERVER_TYPE_BEATDEDI_QUICKPLAY ||
            $this->serverType === self::SERVER_TYPE_NORMAL_QUICKPLAY;
    }

    public function getIsDedicatedServer(): bool
    {
        return $this->serverType && $this->serverType !== self::SERVER_TYPE_PLAYER_HOST;
    }

    public function determineTrueFirstSeen(): DateTime
    {
        if ($this->getIsDedicatedServer() && $this->endpoint) {
            // Dedicated server - identify first seen by endpoint
            $minFirstSeen = HostedGame::query()
                ->select('MIN(first_seen)')
                ->where('endpoint = ?', $this->endpoint->dbSerialize())
                ->querySingleValue();
            if ($minFirstSeen) {
                return new \DateTime($minFirstSeen);
            }
        }

        // Fallback or Player host (old P2P) - use regular first seen value
        return $this->firstSeen;
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

    public function getHashId(): string
    {
        return self::id2hash($this->id);
    }

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
        return "/game/{$this->getHashId()}";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function jsonSerialize(): mixed
    {
        $sz = $this->getPropertyValues();
        unset($sz['ownerId']);
        $sz['key'] = $this->getHashId();
        return $sz;
    }
}