<?php

namespace app\Data\Filters;

use app\BeatSaber\MasterServer;
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
        $baseQuery->select('DISTINCT server_type, master_server_host, id, player_count, player_limit');

        $finalOptions = ["all" => "All servers"];

        foreach ($baseQuery->queryAllRows() as $row) {
            $serverType = $row['server_type'] ?? null;
            $masterServerHost = $row['master_server_host'] ?? null;

            $isOfficial = $masterServerHost === null
                || str_ends_with($masterServerHost, MasterServer::OFFICIAL_HOSTNAME_SUFFIX);

            switch ($serverType) {
                case null:
                case HostedGame::SERVER_TYPE_NORMAL_DEDICATED:
                    if ($isOfficial)
                        $finalOptions["official_dedicated"] = "Official Dedicated";
                    else
                        $finalOptions["other_dedicated"] = "Other Dedicated";
                    break;
                case HostedGame::SERVER_TYPE_PLAYER_HOST:
                    $finalOptions["old_p2p"] = "Old player hosted (P2P)";
                    break;
                case HostedGame::SERVER_TYPE_BEATTOGETHER_DEDICATED:
                    $finalOptions[$serverType] = "BeatTogether Server";
                    break;
                case HostedGame::SERVER_TYPE_BEATTOGETHER_QUICKPLAY:
                    $finalOptions[$serverType] = "BeatTogether Quick Play";
                    break;
                case HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY:
                    if ($isOfficial)
                        $finalOptions["official_quickplay"] = "Official Quick Play";
                    else
                        $finalOptions["other_quickplay"] = "Other Quick Play";
                    break;
                case HostedGame::SERVER_TYPE_BEATNET_CUSTOM:
                case HostedGame::SERVER_TYPE_BEATNET_QUICKPLAY:
                    $finalOptions["beatnet"] = "BeatNet";
                    break;
                default:
                    $finalOptions[$serverType] = $serverType;
                    break;
            }
        }

        return $finalOptions;
    }

    public function applyFilter(ModelQuery $baseQuery, ?string $inputValue): void
    {
        if (empty($inputValue) || $inputValue === "all")
            return;

        if ($inputValue === "official_dedicated") {
            $baseQuery->andWhere('(master_server_host IS NULL OR master_server_host LIKE ?) AND (server_type = ? OR server_type IS NULL)',
                ("%" . MasterServer::OFFICIAL_HOSTNAME_SUFFIX),
                HostedGame::SERVER_TYPE_NORMAL_DEDICATED
            );
        } else if ($inputValue === "other_dedicated") {
            $baseQuery->andWhere('(master_server_host IS NOT NULL AND master_server_host NOT LIKE ?) AND (server_type = ? OR server_type IS NULL)',
                ("%" . MasterServer::OFFICIAL_HOSTNAME_SUFFIX),
                HostedGame::SERVER_TYPE_NORMAL_DEDICATED
            );
        } else if ($inputValue === "official_quickplay") {
            $baseQuery->andWhere('(master_server_host IS NULL OR master_server_host LIKE ?) AND server_type = ?',
                ("%" . MasterServer::OFFICIAL_HOSTNAME_SUFFIX),
                HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY
            );
        } else if ($inputValue === "other_quickplay") {
            $baseQuery->andWhere('(master_server_host IS NOT NULL AND master_server_host NOT LIKE ?) AND server_type = ?',
                ("%" . MasterServer::OFFICIAL_HOSTNAME_SUFFIX),
                HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY
            );
        } else if ($inputValue === "old_p2p") {
            $baseQuery->andWhere('server_type  = ?', [
                HostedGame::SERVER_TYPE_PLAYER_HOST,
            ]);
        } else if ($inputValue === "vanilla_custom") {
            $baseQuery->andWhere('server_type IS NULL OR server_type IN (?)', [
                HostedGame::SERVER_TYPE_NORMAL_DEDICATED,
                HostedGame::SERVER_TYPE_PLAYER_HOST,
            ]);
        } else if ($inputValue === "beatnet") {
            $baseQuery->andWhere('server_type IN (?)', [
                HostedGame::SERVER_TYPE_BEATNET_QUICKPLAY,
                HostedGame::SERVER_TYPE_BEATNET_CUSTOM,
            ]);
        } else {
            $baseQuery->andWhere('server_type = ?', $inputValue);
        }
    }
}