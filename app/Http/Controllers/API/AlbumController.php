<?php

namespace App\Http\Controllers\API;

use App\Exceptions\AlbumNameConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Album\AlbumListRequest;
use App\Http\Requests\API\Album\AlbumUpdateRequest;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use App\Services\AlbumService;
use Illuminate\Validation\ValidationException;

class AlbumController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $repository,
        private readonly AlbumService $service,
    ) {
    }

    public function index(AlbumListRequest $request)
    {
        return AlbumResource::collection($this->repository->getForListing(
            sortColumn: $request->sort ?? 'name',
            sortDirection: $request->order ?? 'asc',
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
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }
    }
}
