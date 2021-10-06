<?php

namespace app\Data;

use app\Data\Filters\BaseFilter;
use app\Models\HostedGame;

class GameQueryResult
{
    /**
     * @var HostedGame[]
     */
    public array $games;

    /**
     * @var BaseFilter[]
     */
    public array $filters;

    /**
     * @var string[]
     */
    public array $filterOptions;

    /**
     * @var string[]
     */
    public array $filterValues;

    public function getIsFiltered(): bool
    {
        return !empty($this->filterValues);
    }
}