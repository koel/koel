<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Services\MediaInformationService;
use Illuminate\Contracts\Auth\Authenticatable;

class AlbumController extends Controller
{
    /** @param User $user */
    public function __construct(
        private AlbumRepository $albumRepository,
        private MediaInformationService $informationService,
        private ?Authenticatable $user
    ) {
    }

    public function index()
    {
        $pagination = Album::withMeta($this->user)
            ->isStandard()
            ->orderBy('albums.name')
            ->simplePaginate(21);

        return AlbumResource::collection($pagination);
    }

    public function show(Album $album)
    {
        $album = $this->albumRepository->getOne($album->id, $this->user);
        $album->information = $this->informationService->getAlbumInformation($album);

        return AlbumResource::make($album);
    }
}
