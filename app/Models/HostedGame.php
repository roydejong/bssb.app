<?php

namespace app\Models;

use app\AWS\GameSessionArn;
use app\AWS\GameSessionArnParser;
use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\BSSB;
use app\Common\CVersion;
use app\Common\IPEndPoint;
use app\Models\Joins\LevelHistoryWithLevelRecord;
use app\Models\Traits\HasBeatmapCharacteristic;
use app\Utils\PirateDetect;
use DateTime;
use Hashids\Hashids;
use SoftwarePunt\Instarecord\Model;

class HostedGame extends Model implements \JsonSerializable
{
    use HasBeatmapCharacteristic;

    // -----------------------------------------------------------------------------------------------------------------
    // Consts

    public const SERVER_TYPE_BEATTOGETHER_DEDICATED = "beattogether_dedicated";
    public const SERVER_TYPE_BEATTOGETHER_QUICKPLAY = "beattogether_quickplay";
    public const SERVER_TYPE_BEATDEDI_CUSTOM = "beatdedi_custom";
    public const SERVER_TYPE_BEATDEDI_QUICKPLAY = "beatdedi_quickplay";
    public const SERVER_TYPE_BEATUPSERVER_DEDICATED = "beatupserver_dedicated";
    public const SERVER_TYPE_BEATUPSERVER_QUICKPLAY = "beatupserver_quickplay";
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
     * Announcer's platform (e.g. "steam", "oculus")
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
     * The multiplayer status check URL associated with the master server.
     */
    public ?string $masterStatusUrl;
    /**
     * Indicates when the announcement was cancelled, or NULL if it was not explicitly cancelled.
     */
    public ?\DateTime $endedAt;
    /**
     * The version of the MultiplayerCore mod the announcer has.
     * Used to verify compatibility; only clients with the exact same version should join.
     * Only available since Server Browser v1.0+, which depends on MultiplayerCore.
     */
    public ?string $mpCoreVersion;
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
     * @var HostedGamePlayer[]|null
     */
    private ?array $players;
    private ?LevelRecord $levelRecord;

    /**
     * @return HostedGamePlayer[]
     */
    public function fetchPlayers(bool $allowCache = true): array
    {
        if (!isset($this->players) || !$allowCache) {
            if ($this->id > 0) {
                $this->players = HostedGamePlayer::query()
                    ->where('hosted_game_id = ?', $this->id)
                    ->orderBy('sort_index ASC')
                    ->queryAllModels();
            } else {
                $this->players = [];
            }
        }
        return $this->players;
    }

    /**
     * @return LevelRecord
     */
    public function fetchLevel(bool $allowCache = true): ?LevelRecord
    {
        if (!isset($this->levelRecord) || !$allowCache) {
            if ($this->levelId) {
                $this->levelRecord = LevelRecord::query()
                    ->where('level_id = ?', $this->levelId)
                    ->querySingleModel();
            } else {
                $this->levelRecord = null;
            }
        }
        return $this->levelRecord;
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

    public function getIsStaleOrEnded(): bool
    {
        return $this->getIsStale() || $this->endedAt;
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
        return empty($this->masterServerHost) && !empty($this->endpoint) && !$this->getIsGameLiftServer();
    }

    public function getIsOfficial(): bool
    {
        if ($this->getIsGameLiftServer())
            return true;

        if ($this->getIsDirectConnect())
            return false;

        return $this->masterServerHost === null
            || strpos($this->masterServerHost, ".mp.beatsaber.com") !== false;
    }

    public function getIsPeerToPeer(): bool
    {
        if ($this->gameVersion && $this->gameVersion->greaterThanOrEquals(new CVersion("1.16.3")))
            // Game version is too new to be P2P
            return false;

        if ($this->serverType === self::SERVER_TYPE_PLAYER_HOST)
            // Server type is specifically marked as P2P
            return true;

        // Default mode: old game version, every non-quickplay game should be P2P
        return !$this->getIsQuickplay();
    }

    public function getIsGameLiftServer(): bool
    {
        return str_starts_with($this->ownerId, "arn:aws:gamelift:");
    }

    public function tryGetGameLiftInfo(): ?GameSessionArn
    {
        if (!$this->getIsGameLiftServer())
            return null;

        return GameSessionArnParser::tryParse($this->ownerId);
    }

    public function tryGetGameLiftRegion(): ?string
    {
        return $this->tryGetGameLiftInfo()?->fleetRegion;
    }

    public function getUsesBeatTogetherMaster(): bool
    {
        if (!$this->masterServerHost)
            return false;

        if ($this->masterServerHost == "btogether.xn--9o8hpe.ws")
            return true;

        return str_ends_with($this->masterServerHost, ".beattogether.systems");
    }

    public function describeMasterServer(): string
    {
        if ($this->getIsGameLiftServer())
            return "GameLift {$this->tryGetGameLiftRegion()}";

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

    public function describeMasterServerSelection(): string
    {
        if ($this->getIsOfficial())
            return "Official";

        if ($this->getIsBeatTogether())
            return "BeatTogether";

        return $this->masterServerHost;
    }

    public function describeSong(): ?string
    {
        $parts = [];

        if ($this->songAuthor)
            $parts[] = $this->songAuthor;
        if ($this->songName)
            $parts[] = $this->songName;

        if (empty($parts))
            if ($this->levelId)
                return $this->levelId;
            else
                return null;

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
            self::SERVER_TYPE_BEATUPSERVER_QUICKPLAY => "BeatUpServer Quickplay",
            self::SERVER_TYPE_BEATUPSERVER_DEDICATED => "BeatUpServer Dedicated",
            null, self::SERVER_TYPE_PLAYER_HOST => "Player hosted (Old P2P)",
            default => $this->serverType
        };
    }

    public function describeGameDetail(): string
    {
        if ($this->getIsQuickplay()) {
            // e.g. "Official Quickplay 1.21.0 Multiplayer Lobby"
            $parts = [
                $this->describeServerType(),
                $this->gameVersion,
                "Multiplayer Lobby"
            ];
        } else {
            // e.g. "Custom 1.21.0 Multiplayer Lobby on BeatTogether Dedicated"
            $parts = array_filter([
                "Custom",
                $this->gameVersion,
                "Multiplayer Lobby",
                "on {$this->describeServerType()}"
            ]);
        }
        return implode(' ', $parts);
    }

    public function getIsInLobby(): bool
    {
        $stateNormal = $this->getAdjustedState();

        return $stateNormal === MultiplayerLobbyState::None ||
            $stateNormal === MultiplayerLobbyState::LobbySetup ||
            $stateNormal === MultiplayerLobbyState::LobbyCountdown ||
            $stateNormal === MultiplayerLobbyState::Error;
    }

    public function getIsPlayingLevel(bool $mustBeInGameplayScene = false): bool
    {
        if (empty($this->levelId))
            return false;

        if ($this->getIsInLobby())
            return false;

        if ($mustBeInGameplayScene)
            return $this->getAdjustedState() == MultiplayerLobbyState::GameRunning;
        else
            return true;
    }

    public function getMinPlayerCount(): int
    {
        if ($this->getIsBeatDedi()) {
            // BeatDedi may report a zero player count
            return 0;
        }

        return 1;
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

    public function describeRequiredMods(): ?string
    {
        $modNames = [];

        if ($this->mpCoreVersion)
            $modNames[] = "MultiplayerCore {$this->mpCoreVersion}";

        if ($this->mpExVersion)
            $modNames[] = "MultiplayerExtensions {$this->mpExVersion}";

        if (empty($modNames))
            return null;

        return implode(" and ", $modNames);
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

    public function jsonSerialize(bool $includeDetails = false, bool $includeLevelObject = false, bool $includeLevelHistory = false): array
    {
        $sz = $this->getPropertyValues();
        $sz['key'] = $this->getHashId();

        if ($this->masterServerHost && $this->masterServerPort)
            $sz['masterServerEp'] = "{$this->masterServerHost}:{$this->masterServerPort}";

        if ($includeDetails) {
            $sz['players'] = $this->serializePlayers();
        }

        if ($includeDetails || $includeLevelObject) {
            $sz['level'] = $this->serializeLevel();
            unset($sz['beatsaverId']);
            unset($sz['coverUrl']);
            unset($sz['levelName']);
            unset($sz['levelId']);
            unset($sz['songName']);
            unset($sz['songAuthor']);
        }

        $sz['serverTypeText'] = $this->describeServerType();
        $sz['masterServerText'] = $this->describeMasterServer();

        if ($includeLevelHistory) {
            $sz['levelHistory'] = LevelHistoryWithLevelRecord::queryHistoryForGame($this->id, 10);
        }

        return $sz;
    }

    public function serializeLevel(): ?array
    {
        return $this->fetchLevel()?->jsonSerialize() ?? null;
    }

    public function serializePlayers(): array
    {
        $sz = [];

        foreach ($this->fetchPlayers() as $player) {
            if (!$player->isConnected)
                continue;

            $sz[] = [
                'userId' => $player->userId,
                'userName' => $player->userName,
                'sortIndex' => $player->sortIndex,
                'isHost' => $player->isHost,
                'isPartyLeader' => $player->userId == $this->managerId,
                'isAnnouncer' => $player->isAnnouncer,
                'latency' => $player->latency
            ];
        }

        return $sz;
    }
}