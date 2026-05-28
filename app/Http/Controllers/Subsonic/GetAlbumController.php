<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetAlbumController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(IdRequest $request, Authenticatable $user)
    {
        $album = $this->albumRepository->getOne($request->id);
        $album->loadCount('songs')->loadSum('songs', 'length');

        $songs = $this->songRepository->getByAlbum($album);

        return SubsonicResponse::ok([
            'album' => AlbumResource::toArray($album, $user)
                + [
                    'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
                ],
        ]);
    }
}
