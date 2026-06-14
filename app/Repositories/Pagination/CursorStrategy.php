<?php

namespace App\Repositories\Pagination;

use App\Repositories\Contracts\PaginationStrategy;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class CursorStrategy implements PaginationStrategy
{
    public function __construct(
        private ?string $cursor,
    ) {}

    public function apply(Builder $builder, string $idColumn, int $perPage): CursorPaginator
    {
        return $builder->orderBy($idColumn)->cursorPaginate($perPage, cursor: $this->cursor);
    }
}
