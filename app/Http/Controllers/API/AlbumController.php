<?php

namespace App\Http\Controllers\API;

use App\Exceptions\AlbumNameConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Album\AlbumUpdateRequest;
use App\Http\Requests\API\CursorPaginatedListRequest;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\Pagination\PaginationStrategyResolver;
use App\Services\AlbumService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AlbumController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly AlbumRepository $repository,
        private readonly AlbumService $service,
        private readonly Authenticatable $user,
    ) {}

    public function index(CursorPaginatedListRequest $request)
    {
        return AlbumResource::collection($this->repository->paginate(
            sortColumn: $request->sort ?? 'name',
            sortDirection: $request->order ?? 'asc',
            strategy: PaginationStrategyResolver::resolve($request),
            favoritesOnly: $request->boolean('favorites_only'),
        ));
    }

    public function show(Album $album)
    {
        // enrich the album with its user context
        return AlbumResource::make($this->repository->getOne($album->id));
    }

    public function update(Album $album, AlbumUpdateRequest $request)
    {
        $this->authorize('update', $album);

        try {
            return AlbumResource::make($this->service->updateAlbum($album, $request->toDto()));
        } catch (AlbumNameConflictException $e) {
            throw_if(
                $this->user->cannot('merge', [$album, $e->existingAlbum]),
                ValidationException::withMessages(['name' => $e->getMessage()]),
            );

            if (!$request->merge) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
            }

            $keptAlbum = $this->service->mergeAlbums($album, $e->existingAlbum);

            return AlbumResource::make($this->repository->getOne($keptAlbum->id));
        }
    }
}
