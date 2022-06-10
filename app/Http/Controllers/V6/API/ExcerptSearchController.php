<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Controllers\V6\Requests\SearchRequest;
use App\Http\Resources\ExcerptSearchResource;
use App\Models\User;
use App\Services\V6\SearchService;
use Illuminate\Contracts\Auth\Authenticatable;

class ExcerptSearchController extends Controller
{
    /** @param User $user */
    public function __invoke(SearchRequest $request, SearchService $searchService, Authenticatable $user)
    {
        return ExcerptSearchResource::make($searchService->excerptSearch($request->q, $user));
    }
}
