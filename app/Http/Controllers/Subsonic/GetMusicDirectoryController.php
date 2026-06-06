<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\Subsonic\DataNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\DirectoryResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetMusicDirectoryController extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(IdRequest $request, Authenticatable $user)
    {
        $album = $this->albumRepository->findOne($request->id);

        if ($album) {
            $songs = $this->songRepository->getByAlbum($album);

            return SubsonicResponse::ok(['directory' => DirectoryResource::forAlbum($album, $songs, $user)]);
        }

        $artist = $this->artistRepository->findOne($request->id);

        if ($artist) {
            $albums = $this->albumRepository->getByArtist($artist);

            return SubsonicResponse::ok(['directory' => DirectoryResource::forArtist($artist, $albums)]);
        }

        throw new DataNotFoundException('Directory not found.');
    }
}
