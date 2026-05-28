<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetStarred2Controller extends Controller
{
    private const int LIMIT = 500;

    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $artists = $this->artistRepository->getFavorites(self::LIMIT, user: $user);
        $albums = $this->albumRepository
            ->getFavorites(self::LIMIT, user: $user)
            ->loadCount('songs')
            ->loadSum('songs', 'length');
        $songs = $this->songRepository->getFavorites(scopedUser: $user)->take(self::LIMIT);

        return SubsonicResponse::ok([
            'starred2' => [
                'artist' => $artists->map(static fn (Artist $artist) => ArtistResource::toArray($artist, $user))->all(),
                'album' => $albums->map(static fn (Album $album) => AlbumResource::toArray($album, $user))->all(),
                'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
            ],
        ]);
    }
}
