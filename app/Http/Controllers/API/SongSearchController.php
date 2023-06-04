<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SearchRequest;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Contracts\Auth\Authenticatable;

class SongSearchController extends Controller
{
    /** @param User $user */
    public function __invoke(SearchRequest $request, SearchService $searchService, Authenticatable $user)
    {
        return SongResource::collection($searchService->searchSongs($request->q, $user));
    }
}
