<?php

namespace App\Repositories\Pagination;

use App\Http\Requests\Request;
use App\Repositories\Contracts\PaginationStrategy;

final readonly class PaginationStrategyResolver
{
    public static function resolve(Request $request): PaginationStrategy
    {
        return $request->has('cursor') ? new CursorStrategy($request->input('cursor')) : new OffsetStrategy();
    }
}
