<?php

namespace App\Repositories\Pagination;

use App\Repositories\Contracts\PaginationStrategy;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final class OffsetStrategy implements PaginationStrategy
{
    public function apply(Builder $builder, string $idColumn, int $perPage): Paginator
    {
        return $builder->simplePaginate($perPage);
    }
}
