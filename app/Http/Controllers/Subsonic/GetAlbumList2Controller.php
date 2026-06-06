<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Subsonic\Concerns\LoadsAlbumsByType;
use App\Http\Requests\Subsonic\GetAlbumList2Request;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetAlbumList2Controller extends Controller
{
    use LoadsAlbumsByType;

    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetAlbumList2Request $request, Authenticatable $user)
    {
        $size = $request->integer('size', 10);
        $offset = $request->integer('offset', 0);

        $albums = $this
            ->loadAlbumsByType($this->albumRepository, $request, $size, $offset)
            ->loadCount('songs')
            ->loadSum('songs', 'length');

        return SubsonicResponse::ok([
            'albumList2' => [
                'album' => $albums->map(static fn (Album $album) => AlbumResource::toArray($album, $user))->all(),
            ],
        ]);
    }
}
