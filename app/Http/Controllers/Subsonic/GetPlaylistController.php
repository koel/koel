<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\PlaylistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Song;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetPlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(IdRequest $request, Authenticatable $user)
    {
        $playlist = $this->playlistRepository->getOne($request->id);
        $this->authorize('access', $playlist);

        $songs = $this->songRepository->getByPlaylist($playlist, $user);
        $totalLength = $songs->sum('length');
        $playlist->setAttribute('playables_count', $songs->count())->setAttribute('playables_sum_length', $totalLength);

        return SubsonicResponse::ok([
            'playlist' => PlaylistResource::toArray($playlist)
                + [
                    'entry' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
                ],
        ]);
    }
}
