<?php

namespace App\Http\Controllers\API\Concerns;

use App\Repositories\Contracts\PaginationStrategy;
use App\Repositories\Pagination\CursorStrategy;
use App\Repositories\Pagination\OffsetStrategy;
use Illuminate\Http\Request;

trait ResolvesPaginationStrategyFromRequest
{
    private function resolvePaginationStrategy(Request $request): PaginationStrategy
    {
        return $request->has('cursor') ? new CursorStrategy($request->input('cursor')) : new OffsetStrategy();
    }
}
