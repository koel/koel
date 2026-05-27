<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\CreatePlaylistRequest;
use App\Http\Responses\Subsonic\Resources\PlaylistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use App\Services\Playlist\PlaylistService;
use App\Values\Playlist\PlaylistCreateData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

class CreatePlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
        private readonly SongRepository $songRepository,
        private readonly PlaylistService $playlistService,
    ) {}

    /** @param User $user */
    public function __invoke(CreatePlaylistRequest $request, Authenticatable $user)
    {
        /** @var list<string> $songIds */
        $songIds = Arr::wrap($request->input('songId', []));

        $playlist = $request->input('playlistId')
            ? $this->replaceContents($request->input('playlistId'), $request->input('name'), $songIds, $user)
            : $this->playlistService->createPlaylist(
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

    /** @param list<string> $songIds */
    private function replaceContents(string $playlistId, ?string $name, array $songIds, User $user): Playlist
    {
        $playlist = $this->playlistRepository->getOne($playlistId);
        $this->authorize('edit', $playlist);

        throw_if($playlist->is_smart, new OperationNotApplicableForSmartPlaylistException());

        if ($name !== null) {
            $playlist->update(['name' => $name]);
        }

        $playlist->playables()->detach();

        if ($songIds) {
            $songs = $this->songRepository->getMany($songIds);
            $this->playlistService->addPlayablesToPlaylist($playlist, $songs, $user);
        }

        return $playlist->refresh();
    }
}
