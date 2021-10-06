<?php

namespace app\Data\Filters;

use SoftwarePunt\Instarecord\Database\ModelQuery;

class GameVersionFilter extends BaseFilter
{
    public function getId(): string
    {
        return "gameVersion";
    }

    public function getLabel(): string
    {
        return "Game version";
    }

    public function queryOptions(ModelQuery $baseQuery): array
    {
        $options = ['all' => 'All versions'];

        $baseQuery->select('DISTINCT hosted_games.game_version');

        foreach ($baseQuery->querySingleValueArray() as $gameVersionOption) {
            $options[$gameVersionOption] = $gameVersionOption;
        }

        return $options;
    }

    public function applyFilter(ModelQuery $baseQuery, ?string $inputValue): void
    {
        if (empty($inputValue) || $inputValue === "all")
            return;

        $baseQuery->andWhere('game_version = ?', $inputValue);
    }
}