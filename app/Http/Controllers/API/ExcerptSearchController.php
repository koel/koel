<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SearchRequest;
use App\Http\Resources\ExcerptSearchResource;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Contracts\Auth\Authenticatable;

class ExcerptSearchController extends Controller
{
    /** @param User $user */
    public function __invoke(SearchRequest $request, SearchService $searchService, Authenticatable $user)
    {
        return ExcerptSearchResource::make($searchService->excerptSearch($request->q, $user));
    }
}
