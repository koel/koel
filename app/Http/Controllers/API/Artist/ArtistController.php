<?php

namespace App\Http\Controllers\API\Artist;

use App\Exceptions\ArtistNameConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Artist\ArtistListRequest;
use App\Http\Requests\API\Artist\ArtistUpdateRequest;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Repositories\ArtistRepository;
use App\Services\ArtistService;
use Illuminate\Validation\ValidationException;

class ArtistController extends Controller
{
    public function __construct(
        private readonly ArtistService $service,
        private readonly ArtistRepository $repository,
    ) {
    }

    public function index(ArtistListRequest $request)
    {
        return ArtistResource::collection($this->repository->getForListing(
            sortColumn: $request->sort ?? 'name',
            sortDirection: $request->order ?? 'asc',
            favoritesOnly: $request->boolean('favorites_only'),
        ));
    }

    public function show(Artist $artist)
    {
        // enrich the artist with its user context
        return ArtistResource::make($this->repository->getOne($artist->id));
    }

    public function update(Artist $artist, ArtistUpdateRequest $request)
    {
        $this->authorize('update', $artist);

        try {
            return ArtistResource::make($this->service->updateArtist($artist, $request->toDto()));
        } catch (ArtistNameConflictException $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }
    }
}
