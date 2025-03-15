<?php

namespace app\External;

use app\BeatSaber\Enums\MultiplayerAvailabilityStatus;
use app\Common\CVersion;

final class MasterServerStatus
{
    // -----------------------------------------------------------------------------------------------------------------
    // Data

    private ?string $originalJson;

    public ?CVersion $minimumAppVersion = null;
    public ?MultiplayerAvailabilityStatus $status = null;
    public ?\DateTime $maintenanceStartTime = null;
    public ?\DateTime $maintenanceEndTime = null;
    public bool $useGamelift = false;
    public bool $useSsl = false;
    public ?string $name = null;
    public ?string $description = null;
    public ?string $imageUrl = null;
    public ?int $maxPlayers = null;

    private function __construct(string $originalJson)
    {
        $this->originalJson = $originalJson;
    }

    public function asArray(): array
    {
        return [
            'minimum_app_version' => $this->minimumAppVersion,
            'status' => $this->status?->value ?? 0,
            'maintenance_start_time' => $this->maintenanceStartTime?->getTimestamp() ?? null,
            'maintenance_end_time' => $this->maintenanceEndTime?->getTimestamp() ?? null,
            'use_gamelift' => $this->useGamelift,
            'use_ssl' => $this->useSsl,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'max_players' => $this->maxPlayers
        ];
    }

    public function asJson(): string
    {
        if ($this->originalJson)
            return $this->originalJson;

        return json_encode($this->asArray());
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Parse

    public static function fromJson(string $jsonRaw, bool $isOfficial = false): ?MasterServerStatus
    {
        $jsonDecoded = @json_decode($jsonRaw, true);

        if (empty($jsonDecoded))
            return null;

        if (!empty($jsonDecoded['data']))
            return MasterServerStatus::fromData($jsonDecoded['data'][0], $jsonRaw, $isOfficial);

        return MasterServerStatus::fromData($jsonDecoded, $jsonRaw, $isOfficial);
    }

    public static function fromData(array $data, string $originalJson, bool $isOfficial = false): MasterServerStatus
    {
        $status = new MasterServerStatus($originalJson);
        $status->minimumAppVersion = CVersion::tryParse($data['minimumAppVersion'] ?? $data['minimum_app_version'] ?? null);
        $status->status = MultiplayerAvailabilityStatus::tryFrom(intval($data['status'] ?? null));
        $status->maintenanceStartTime = self::tryParseTimestamp(intval($data['maintenance_start_time'] ?? $data['maintenanceStartTime'] ?? 0));
        $status->maintenanceEndTime = self::tryParseTimestamp(intval($data['maintenance_end_time'] ?? $data['maintenanceEndTime'] ?? 0));
        $status->useGamelift = intval($data['use_gamelift'] ?? $data['useGamelift'] ?? 0) === 1;
        $status->useSsl = $isOfficial || intval($data['use_ssl'] ?? $data['useSsl'] ?? 0) === 1;
        $status->name = $data['name'] ?? null;
        $status->description = $data['description'] ?? null;
        $status->imageUrl = $data['image_url'] ?? $data['imageUrl'] ?? null;
        $status->maxPlayers = intval($data['max_players'] ?? $data['maxPlayers'] ?? 0);
        return $status;
    }

    private static function tryParseTimestamp(?int $unixTimestamp): ?\DateTime
    {
        if ($unixTimestamp <= 0)
            return null;

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($unixTimestamp);
        return $dateTime;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Fetch

    const LiveGameServiceEnv = "ProductionC";

    public static function tryFetch(string $statusUrl): ?MasterServerStatus
    {
        if (!str_starts_with($statusUrl, "https://") && !str_starts_with($statusUrl, "http://"))
            return null;

        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: BeatSaberServerBrowser API (https://bssb.app)\r\n"
            ]
        ]);

        $isOfficial = str_starts_with($statusUrl, "https://graph.oculus.com/");

        if (str_ends_with($statusUrl, "/beat_saber_multiplayer_status")) {
            $statusUrl .= "?access_token=OC%7C238236400888545%7C&service_environment=" . self::LiveGameServiceEnv;
        }

        $rawResponse = @file_get_contents($statusUrl, context: $context);

        if (!$rawResponse)
            return null;

        return MasterServerStatus::fromJson($rawResponse, $isOfficial);
    }
}