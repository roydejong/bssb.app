<?php

namespace app\Data\Filters;

use app\Models\HostedGame;
use SoftwarePunt\Instarecord\Database\ModelQuery;

class ServerTypeFilter extends BaseFilter
{
    public function getId(): string
    {
        return "serverType";
    }

    public function getLabel(): string
    {
        return "Server type";
    }

    public function queryOptions(ModelQuery $baseQuery): array
    {
        $baseQuery->select('DISTINCT server_type, id, player_count, player_limit');

        $finalOptions = ["all" => "All servers"];

        foreach ($baseQuery->querySingleValueArray() as $serverTypeOption) {
            switch ($serverTypeOption) {
                case null:
                case HostedGame::SERVER_TYPE_VANILLA_DEDICATED:
                case HostedGame::SERVER_TYPE_PLAYER_HOST:
                    $finalOptions["vanilla_custom"] = "Vanilla Custom";
                    break;
                case HostedGame::SERVER_TYPE_BEATTOGETHER_DEDICATED:
                    $finalOptions[$serverTypeOption] = "BeatTogether Custom";
                    break;
                case HostedGame::SERVER_TYPE_BEATTOGETHER_QUICKPLAY:
                    $finalOptions[$serverTypeOption] = "BeatTogether Quick Play";
                    break;
                case HostedGame::SERVER_TYPE_VANILLA_QUICKPLAY:
                    $finalOptions[HostedGame::SERVER_TYPE_VANILLA_QUICKPLAY] = "Vanilla Quick Play";
                    break;
                case HostedGame::SERVER_TYPE_BEATDEDI_CUSTOM:
                case HostedGame::SERVER_TYPE_BEATDEDI_QUICKPLAY:
                    $finalOptions["beatdedi"] = "BeatDedi";
                    break;
                default:
                    $finalOptions[$serverTypeOption] = $serverTypeOption;
                    break;
            }
        }

        return $finalOptions;
    }

    public function applyFilter(ModelQuery $baseQuery, ?string $inputValue): void
    {
        if (empty($inputValue) || $inputValue === "all")
            return;

        if ($inputValue === "vanilla_custom") {
            $baseQuery->andWhere('server_type IS NULL OR server_type IN (?)', [
                HostedGame::SERVER_TYPE_VANILLA_DEDICATED,
                HostedGame::SERVER_TYPE_PLAYER_HOST,
            ]);
        } else if ($inputValue === "beatdedi") {
            $baseQuery->andWhere('server_type IN (?)', [
                HostedGame::SERVER_TYPE_BEATDEDI_QUICKPLAY,
                HostedGame::SERVER_TYPE_BEATDEDI_CUSTOM,
            ]);
        } else {
            $baseQuery->andWhere('server_type = ?', $inputValue);
        }
    }
}