<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SearchRequest;
use App\Http\Resources\ExcerptSearchResource;
use App\Services\SearchService;

class ExcerptSearchController extends Controller
{
    public function __invoke(SearchRequest $request, SearchService $searchService)
    {
        return ExcerptSearchResource::make($searchService->excerptSearch($request->q));
    }
}
