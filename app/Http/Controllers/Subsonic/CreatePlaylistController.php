<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\CreatePlaylistRequest;
use App\Http\Responses\Subsonic\Resources\PlaylistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use App\Services\Playlist\PlaylistService;
use App\Values\Playlist\PlaylistCreateData;
use Illuminate\Contracts\Auth\Authenticatable;

class CreatePlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlistService,
        private readonly SongRepository $songRepository,
        private readonly PlaylistRepository $playlistRepository,
    ) {}

    /** @param User $user */
    public function __invoke(CreatePlaylistRequest $request, Authenticatable $user)
    {
        $playlist = $request->playlistId
            ? $this->replaceSongsInPlaylist($request->playlistId, $request->songId, $user)
            : $this->playlistService->createPlaylist(
                PlaylistCreateData::make(name: $request->name, playableIds: $request->songId),
                $user,
            );

        $playlist->loadCount('playables')->loadSum('playables', 'length');

        $songs = $this->songRepository->getByPlaylist($playlist, $user);

        return SubsonicResponse::ok([
            'playlist' => PlaylistResource::toArray($playlist)
                + [
                    'entry' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
                ],
        ]);
    }

    /**
     * @param array<string> $songIds
     */
    private function replaceSongsInPlaylist(string $playlistId, array $songIds, User $user): Playlist
    {
        $playlist = $this->playlistRepository->getOne($playlistId);
        $this->authorize('own', $playlist);

        throw_if($playlist->is_smart, OperationNotApplicableForSmartPlaylistException::class);

        $accessibleSongIds = $this->songRepository
            ->getMany($songIds, preserveOrder: true, scopedUser: $user)
            ->modelKeys();

        $this->playlistService->replacePlayablesInPlaylist($playlist, $accessibleSongIds, $user);

        return $playlist;
    }
}
