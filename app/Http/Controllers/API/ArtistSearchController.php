<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ListSearchRequest;
use App\Http\Resources\ArtistResource;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Contracts\Auth\Authenticatable;

class ArtistSearchController extends Controller
{
    /** @param User $user */
    public function __invoke(ListSearchRequest $request, SearchService $searchService, Authenticatable $user)
    {
        return ArtistResource::collection($searchService->searchArtists(
            $request->q,
            $user,
            $request->boolean('favorites_only'),
        ));
    }
}
