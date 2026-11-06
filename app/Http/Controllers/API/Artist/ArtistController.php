<?php

namespace App\Http\Controllers\API\Artist;

use App\Exceptions\ArtistNameConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Artist\ArtistUpdateRequest;
use App\Http\Requests\API\CursorPaginatedListRequest;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Repositories\Pagination\PaginationStrategyResolver;
use App\Services\ArtistService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ArtistController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly ArtistService $service,
        private readonly ArtistRepository $repository,
        private readonly Authenticatable $user,
    ) {}

    public function index(CursorPaginatedListRequest $request)
    {
        return ArtistResource::collection($this->repository->paginate(
            sortColumn: $request->sort ?? 'name',
            sortDirection: $request->order ?? 'asc',
            strategy: PaginationStrategyResolver::resolve($request),
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
            throw_if(
                $this->user->cannot('merge', [$artist, $e->existingArtist]),
                ValidationException::withMessages(['name' => $e->getMessage()]),
            );

            if (!$request->merge) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
            }

            $keptArtist = $this->service->mergeArtists($artist, $e->existingArtist);

            return ArtistResource::make($this->repository->getOne($keptArtist->id));
        }
    }
}
