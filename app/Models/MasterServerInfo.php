<?php

namespace app\Models;

use app\BeatSaber\MasterServer;
use app\Common\CVersion;
use app\External\GeoIp;
use app\External\MasterServerStatus;
use app\Utils\UrlInfo;
use SoftwarePunt\Instarecord\Database\Column;
use SoftwarePunt\Instarecord\Model;

class MasterServerInfo extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    public int $id;
    public ?string $graphUrl;
    public bool $useSsl;
    /**
     * Legacy master server hostname / IP.
     */
    public ?string $host;
    /**
     * Legacy master server port.
     */
    public ?int $port;
    public ?string $statusUrl;
    public bool $lockStatusUrl;
    public ?string $resolvedIp;
    public ?string $geoipCountry;
    public ?string $geoipText;
    /**
     * Server display name (from status API response).
     */
    public ?string $niceName;
    /**
     * Server description (from status API response).
     */
    public ?string $description;
    /**
     * Maximum amount of players (from status API response).
     */
    public ?string $imageUrl;
    /**
     * Maximum amount of players (from status API response).
     */
    public ?int $maxPlayers;
    public bool $isOfficial;
    public \DateTime $firstSeen;
    public \DateTime $lastSeen;
    public ?string $lastStatusJson;
    public ?\DateTime $lastUpdated;
    public bool $hide;

    // -----------------------------------------------------------------------------------------------------------------
    // Table

    public function getTableName(): string
    {
        return "master_server_info";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Data

    public function getSupportsGraph(): bool
    {
        return !empty($this->graphUrl);
    }

    public function getSupportsLegacy(): bool
    {
        if (empty($this->host) || empty($this->port))
            // Missing info
            return false;

        $status = $this->getLastStatus();

        if ($status?->minimumAppVersion && $status->minimumAppVersion->greaterThanOrEquals(new CVersion("1.29.0")))
            // Not supported (anymore) by minimum app version
            return false;

        return true;
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
        if ($this->graphUrl) {
            // Modern master server with Graph API (>=1.29)
            $urlInfo = new UrlInfo($this->graphUrl);

            // DNS Resolve
            $this->resolvedIp = gethostbyname($urlInfo->host);
            if ($this->resolvedIp === $this->host) {
                // If gethostbyname() fails it seems to just return the hostname itself
                $this->resolvedIp = null;
            }
        } else {
            // Legacy master server (<=1.28)
            if (filter_var($this->host, FILTER_VALIDATE_IP) !== false) {
                // Hostname is valid IP
                $this->resolvedIp = $this->host;
            } else {
                // DNS Resolve
                $this->resolvedIp = gethostbyname($this->host);
                if ($this->resolvedIp === $this->host) {
                    // If gethostbyname() fails it seems to just return the hostname itself
                    $this->resolvedIp = null;
                }
            }
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

                // Status fields copy
                if ($statusObj = $this->getLastStatus()) {
                    if ($statusObj->name)
                        $this->niceName = $statusObj->name;
                    if ($statusObj->description)
                        $this->description = $statusObj->description;
                    if ($statusObj->imageUrl)
                        $this->imageUrl = $statusObj->imageUrl;
                    if ($statusObj->maxPlayers)
                        $this->maxPlayers = $statusObj->maxPlayers;
                    $this->useSsl = $this->isOfficial || $statusObj->useSsl;
                }
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

    public static function fetchOrCreateForGraphUrl(string $graphUrl): MasterServerInfo
    {
        $urlInfo = new UrlInfo($graphUrl);
        $urlNormalized = $urlInfo->rebuild();

        $cacheKey = $urlInfo->host;
        $masterServerInfo = self::$_cachedInfos[$cacheKey] ?? null;

        if (!$masterServerInfo) {
            $nowStr = (new \DateTime('now'))->format(Column::DATE_TIME_FORMAT);

            // Try lookup by host (merge with legacy records whenever possible)
            $masterServerInfo = MasterServerInfo::query()
                ->where('host = ?', $urlInfo->host)
                ->querySingleModel();

            /**
             * @var $masterServerInfo MasterServerInfo|null
             */
            if ($masterServerInfo) {
                // Add Graph URL to legacy record
                $masterServerInfo->graphUrl = $graphUrl;
                $masterServerInfo->save();
            } else {
                // Upsert new/existing record
                $recordId = MasterServerInfo::query()
                    ->insert()
                    ->values(['graph_url' => $urlNormalized, 'host' => $urlInfo->host, 'port' => null, 'first_seen' => $nowStr, 'last_seen' => $nowStr])
                    ->onDuplicateKeyUpdate(['graph_url' => $urlNormalized], 'id')
                    ->executeInsert();

                $masterServerInfo = MasterServerInfo::fetch($recordId);

                if (empty($masterServerInfo->host)) {
                    // Add hostname to modern record for grouping purposes
                    $masterServerInfo->host = $urlInfo->host;
                    $masterServerInfo->save();
                }
            }
        }

        self::$_cachedInfos[$cacheKey] = $masterServerInfo;
        return $masterServerInfo;
    }

    public static function fetchOrCreateForLegacyMaster(string $host, int $port): MasterServerInfo
    {
        $cacheKey = $host;
        $masterServerInfo = self::$_cachedInfos[$cacheKey] ?? null;

        if (!$masterServerInfo) {
            $nowStr = (new \DateTime('now'))->format(Column::DATE_TIME_FORMAT);

            $recordId = MasterServerInfo::query()
                ->insert()
                ->values(['graph_url' => null, 'host' => $host, 'port' => $port, 'first_seen' => $nowStr, 'last_seen' => $nowStr])
                ->onDuplicateKeyUpdate(['host' => $host, 'port' => $port], 'id')
                ->executeInsert();

            $masterServerInfo = MasterServerInfo::fetch($recordId);
        }

        self::$_cachedInfos[$cacheKey] = $masterServerInfo;
        return $masterServerInfo;
    }

    public static function syncFromGame(HostedGame $game): ?MasterServerInfo
    {
        if (!$game->masterGraphUrl && (!$game->masterServerHost || !$game->masterServerPort))
            // Not enough data
            return null;

        if ($game->masterGraphUrl) {
            $masterServerInfo = self::fetchOrCreateForGraphUrl($game->masterGraphUrl);
        } else {
            $masterServerInfo = self::fetchOrCreateForLegacyMaster($game->masterServerHost, $game->masterServerPort);
        }

        $masterServerInfo->setStatusUrlIfBetter($game->masterStatusUrl);

        $masterServerInfo->isOfficial = MasterServer::getHostnameIsOfficial($masterServerInfo->host);

        if ($game->firstSeen < $masterServerInfo->firstSeen)
            $masterServerInfo->firstSeen = $game->firstSeen;

        if ($game->lastUpdate > $masterServerInfo->lastSeen)
            $masterServerInfo->lastSeen = $game->lastUpdate;

        $masterServerInfo->save();

        return $masterServerInfo;
    }

    /**
     * Updates the Multiplayer Status URL, but only if it does not appear to be or a "downgrade".
     * Context: Users don't always configure this correctly, so we have to filter out the noise.
     *
     * @param string|null $statusUrl
     * @return void
     */
    private function setStatusUrlIfBetter(?string $statusUrl): void
    {
        if ($this->lockStatusUrl)
            // Locked
            return;

        if (empty($statusUrl))
            // Do not allow status URLs to ever be removed
            return;

        if ($this->statusUrl && str_ends_with($statusUrl, "://master.beattogether.systems/status"))
            // Do not allow re-use of BeatTogether status URL if we already have a status URL
            return;

        $this->statusUrl = $statusUrl;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function serializeForConfig(): array
    {
        return [
            'graphUrl' => $this->graphUrl,
            'statusUrl' => $this->statusUrl,
            'name' => $this->niceName,
            'description' => $this->description,
            'imageUrl' => $this->imageUrl,
            'maxPlayers' => $this->maxPlayers,
            'useSsl' => $this->useSsl
        ];
    }
}