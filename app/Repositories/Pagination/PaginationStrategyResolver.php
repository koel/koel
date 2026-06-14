<?php

namespace App\Repositories\Pagination;

use App\Repositories\Contracts\PaginationStrategy;
use Illuminate\Http\Request;

final readonly class PaginationStrategyResolver
{
    public static function resolve(Request $request): PaginationStrategy
    {
        return $request->has('cursor') ? new CursorStrategy($request->input('cursor')) : new OffsetStrategy();
    }
}
