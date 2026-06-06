<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\CreatePlaylistRequest;
use App\Http\Responses\Subsonic\Resources\PlaylistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Song;
use App\Models\User;
use App\Services\Playlist\PlaylistService;
use App\Values\Playlist\PlaylistCreateData;
use Illuminate\Contracts\Auth\Authenticatable;

class CreatePlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlistService,
    ) {}

    /** @param User $user */
    public function __invoke(CreatePlaylistRequest $request, Authenticatable $user)
    {
        $playlist = $this->playlistService->createPlaylist(
            PlaylistCreateData::make(name: $request->name, playableIds: $request->songId),
            $user,
        );

        $playlist->loadCount('playables')->loadSum('playables', 'length');

        return SubsonicResponse::ok([
            'playlist' => PlaylistResource::toArray($playlist)
                + [
                    'entry' => $playlist->playables->map(
                        static fn (Song $song) => SongResource::toArray($song, $user),
                    )->all(),
                ],
        ]);
    }
}
