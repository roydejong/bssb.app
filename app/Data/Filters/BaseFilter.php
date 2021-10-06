<?php

namespace app\Data\Filters;

use SoftwarePunt\Instarecord\Database\ModelQuery;

abstract class BaseFilter
{
    public function getId(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public abstract function getLabel(): string;

    public abstract function queryOptions(ModelQuery $baseQuery): array;

    public abstract function applyFilter(ModelQuery $baseQuery, ?string $inputValue);
}