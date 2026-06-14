<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

interface PaginationStrategy
{
    public function apply(Builder $builder, string $idColumn, int $perPage): Paginator|CursorPaginator;
}
