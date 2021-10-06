<?php

namespace app\Data\Filters;

use SoftwarePunt\Instarecord\Database\ModelQuery;

class ModdedLobbyFilter extends BaseFilter
{
    public function getId(): string
    {
        return "modded";
    }

    public function getLabel(): string
    {
        return "Modded lobby";
    }

    public function queryOptions(ModelQuery $baseQuery): array
    {
        $options = [
            'all' => 'All lobbies',
            'vanilla' => 'Vanilla only',
            'modded' => 'Modded only (any version)'
        ];

        $baseQuery->select('DISTINCT hosted_games.mp_ex_version');
        $baseQuery->andWhere('is_modded = 1');

        foreach ($baseQuery->querySingleValueArray() as $mpExVersionOption) {
            $options["mpex_{$mpExVersionOption}"] = "MultiplayerExtensions {$mpExVersionOption}";
        }

        return $options;
    }

    public function applyFilter(ModelQuery $baseQuery, ?string $inputValue): void
    {
        if (empty($inputValue) || $inputValue === "all")
            return;

        if ($inputValue === "vanilla") {
            $baseQuery->andWhere('is_modded = 0');
        } else if ($inputValue === "modded") {
            $baseQuery->andWhere('is_modded = 1');
        } else {
            $baseQuery->andWhere('is_modded = 1');

            $optionParts = explode('_', $inputValue, 2);
            $mpExVersionStr = $optionParts[1] ?? null;

            if ($mpExVersionStr) {
                $baseQuery->andWhere('mp_ex_version = ?', $mpExVersionStr);
            }
        }
    }
}