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
            'use_gamelift' => $this->useGamelift
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

    public static function fromJson(string $jsonRaw): ?MasterServerStatus
    {
        $jsonDecoded = @json_decode($jsonRaw, true);

        if (empty($jsonDecoded))
            return null;

        if (!empty($jsonDecoded['data']))
            return MasterServerStatus::fromData($jsonDecoded['data'][0], $jsonRaw);

        return MasterServerStatus::fromData($jsonDecoded, $jsonRaw);
    }

    public static function fromData(array $data, string $originalJson): MasterServerStatus
    {
        $status = new MasterServerStatus($originalJson);
        $status->minimumAppVersion = CVersion::tryParse($data['minimumAppVersion'] ?? $data['minimum_app_version'] ?? null);
        $status->status = MultiplayerAvailabilityStatus::tryFrom(intval($data['status'] ?? null));
        $status->maintenanceStartTime = self::tryParseTimestamp(intval($data['maintenance_start_time'] ?? $data['maintenanceStartTime'] ?? 0));
        $status->maintenanceEndTime = self::tryParseTimestamp(intval($data['maintenance_end_time'] ?? $data['maintenanceEndTime'] ?? 0));
        $status->useGamelift = intval($data['use_gamelift'] ?? $data['useGamelift'] ?? 0) === 1;
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

        if (str_ends_with($statusUrl, "/beat_saber_multiplayer_status")) {
            $statusUrl .= "?access_token=OC%7C238236400888545%7C&service_environment=ProductionA";
        }

        $rawResponse = @file_get_contents($statusUrl, context: $context);

        if (!$rawResponse)
            return null;

        return MasterServerStatus::fromJson($rawResponse);
    }
}