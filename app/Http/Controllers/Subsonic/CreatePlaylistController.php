<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\CreatePlaylistRequest;
use App\Http\Responses\Subsonic\Resources\PlaylistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\Playlist\PlaylistService;
use App\Values\Playlist\PlaylistCreateData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

class CreatePlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlistService,
    ) {}

    /** @param User $user */
    public function __invoke(CreatePlaylistRequest $request, Authenticatable $user)
    {
        /** @var list<string> $songIds */
        $songIds = Arr::wrap($request->input('songId', []));

        $playlist = $this->playlistService->createPlaylist(
            PlaylistCreateData::make(name: (string) $request->input('name'), playableIds: $songIds),
            $user,
        );

        $playlist->loadCount('playables')->loadSum('playables', 'length');

        return SubsonicResponse::ok([
            'playlist' => PlaylistResource::toArray($playlist)
                + [
                    'entry' => $playlist->playables->map(SongResource::toArray(...))->all(),
                ],
        ]);
    }
}
