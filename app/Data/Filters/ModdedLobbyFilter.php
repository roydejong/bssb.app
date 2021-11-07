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

        $baseQuery->select('DISTINCT is_modded, mp_ex_version, id, player_count, player_limit');

        $anyUnmodded = false;
        $anyModded = false;

        foreach ($baseQuery->queryAllRows() as $row) {
            $isModded = $row['is_modded'] == 1;
            $mpExVersion = $row['mp_ex_version'];

            if ($isModded) {
                $anyModded = true;
            } else {
                $anyUnmodded = true;
            }

            if ($mpExVersion) {
                $mpExOptionKey = "mpex_{$mpExVersion}";

                if (!isset($options[$mpExOptionKey])) {
                    $options[$mpExOptionKey] = "MultiplayerExtensions {$mpExVersion}";
                }
            }
        }

        if (!$anyUnmodded)
            unset($options['vanilla']);

        if (!$anyModded)
            unset($options['modded']);

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