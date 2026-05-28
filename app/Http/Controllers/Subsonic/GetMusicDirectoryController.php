<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Album;
use App\Models\Song;
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
            return $this->renderAlbum($album, $user);
        }

        $artist = $this->artistRepository->findOne($request->id);

        if ($artist) {
            $albums = $this->albumRepository->getByArtist($artist);

            return SubsonicResponse::ok([
                'directory' => [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'child' => $albums->map(self::albumAsChild(...))->all(),
                ],
            ]);
        }

        return SubsonicResponse::error(70, 'Directory not found.');
    }

    private function renderAlbum(Album $album, User $user)
    {
        $songs = $this->songRepository->getByAlbum($album);

        return SubsonicResponse::ok([
            'directory' => [
                'id' => $album->id,
                'parent' => $album->artist_id,
                'name' => $album->name,
                'child' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
            ],
        ]);
    }

    /**
     * @return array{
     *     id: string,
     *     parent: string,
     *     isDir: bool,
     *     title: string,
     *     artist: string,
     *     artistId: string,
     *     coverArt: ?string,
     *     year: ?int,
     *     created: string,
     * }
     */
    private static function albumAsChild(Album $album): array
    {
        return [
            'id' => $album->id,
            'parent' => $album->artist_id,
            'isDir' => true,
            'title' => $album->name,
            'artist' => $album->artist_name,
            'artistId' => $album->artist_id,
            'coverArt' => $album->cover ? $album->id : null,
            'year' => $album->year,
            'created' => $album->created_at->toIso8601String(),
        ];
    }
}
