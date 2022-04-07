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

    public int $pageIndex = 0;
    public int $pageSize = GameQuery::DefaultPageSize;
    public int $pageCount = 1;
    public bool $isFirstPage = false;
    public bool $isLastPage = false;
    public bool $isValidPage = false;

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