<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Controllers\V6\Requests\SearchRequest;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Services\V6\SearchService;
use Illuminate\Contracts\Auth\Authenticatable;

class SongSearchController extends Controller
{
    /** @param User $user */
    public function __invoke(SearchRequest $request, SearchService $searchService, Authenticatable $user)
    {
        return SongResource::collection($searchService->searchSongs($request->q, $user));
    }
}
