<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Subsonic\Concerns\LoadsAlbumsByType;
use App\Http\Requests\Subsonic\GetAlbumListRequest;
use App\Http\Responses\Subsonic\Resources\AlbumChildResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\AlbumRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetAlbumListController extends Controller
{
    use LoadsAlbumsByType;

    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetAlbumListRequest $request, Authenticatable $user)
    {
        $size = $request->integer('size', 10);
        $offset = $request->integer('offset', 0);

        $albums = $this->loadAlbumsByType($this->albumRepository, $request, $size, $offset);

        return SubsonicResponse::ok([
            'albumList' => [
                'album' => $albums->map(AlbumChildResource::toArray(...))->all(),
            ],
        ]);
    }
}
