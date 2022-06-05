<?php

namespace app\Models;

use app\BeatSaber\MasterServer;
use app\External\GeoIp;
use app\External\MasterServerStatus;
use SoftwarePunt\Instarecord\Database\Column;
use SoftwarePunt\Instarecord\Model;

class MasterServerInfo extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    public int $id;
    public string $host;
    public int $port;
    public ?string $statusUrl;
    public ?string $resolvedIp;
    public ?string $geoipCountry;
    public ?string $geoipText;
    public ?string $niceName;
    public bool $isOfficial;
    public \DateTime $firstSeen;
    public \DateTime $lastSeen;
    public ?string $lastStatusJson;
    public ?\DateTime $lastUpdated;

    // -----------------------------------------------------------------------------------------------------------------
    // Table

    public function getTableName(): string
    {
        return "master_server_info";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Status check

    public function getLastStatus(): ?MasterServerStatus
    {
        if (!$this->lastStatusJson)
            return null;

        return MasterServerStatus::fromJson($this->lastStatusJson);
    }

    public function refreshStatus(): void
    {
        // DNS Resolve
        $this->resolvedIp = gethostbyname($this->host);

        if ($this->resolvedIp === $this->host) {
            // If gethostbyname() fails it seems to just return the hostname itself
            $this->resolvedIp = null;
        }

        // GeoIP
        if ($this->resolvedIp) {
            $geoIp = GeoIp::instance();

            $this->geoipCountry = $geoIp->getCountryCode($this->resolvedIp);
            $this->geoipText = $geoIp->describeLocation($this->resolvedIp);
        }

        // Live status check
        if ($this->statusUrl) {
            if ($status = MasterServerStatus::tryFetch($this->statusUrl)) {
                $this->lastStatusJson = $status->asJson();
            } else {
                $this->lastStatusJson = null;
            }
        }

        $this->lastUpdated = new \DateTime('now');
        $this->save();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Cached read/write

    private static array $_cachedInfos = [];

    public static function fetchOrCreate(string $host, int $port): MasterServerInfo
    {
        $cacheKey = "{$host}:{$port}";

        $masterServerInfo = self::$_cachedInfos[$cacheKey] ?? null;

        if (!$masterServerInfo) {
            $nowStr = (new \DateTime('now'))->format(Column::DATE_TIME_FORMAT);

            $recordId = MasterServerInfo::query()
                ->insert()
                ->values(['host' => $host, 'port' => $port, 'first_seen' => $nowStr, 'last_seen' => $nowStr])
                ->onDuplicateKeyUpdate(['host' => $host, 'port' => $port], 'id')
                ->executeInsert();

            $masterServerInfo = MasterServerInfo::fetch($recordId);
        }

        self::$_cachedInfos[$cacheKey] = $masterServerInfo;
        return $masterServerInfo;
    }

    public static function syncFromGame(HostedGame $game): ?MasterServerInfo
    {
        if (!$game->masterServerHost || !$game->masterServerPort)
            return null;

        $masterServerInfo = self::fetchOrCreate($game->masterServerHost, $game->masterServerPort);
        $masterServerInfo->statusUrl = $game->masterStatusUrl;
        $masterServerInfo->isOfficial = MasterServer::getHostnameIsOfficial($masterServerInfo->host);

        if ($game->firstSeen >= $masterServerInfo->firstSeen)
            $masterServerInfo->firstSeen = $game->firstSeen;

        if ($game->lastUpdate >= $masterServerInfo->lastSeen)
            $masterServerInfo->lastSeen = $game->lastUpdate;

        $masterServerInfo->save();

        return $masterServerInfo;
    }
}