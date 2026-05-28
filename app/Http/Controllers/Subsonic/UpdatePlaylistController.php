<?php

namespace App\Http\Controllers\Subsonic;

use App\Exceptions\OperationNotApplicableForSmartPlaylistException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\UpdatePlaylistRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use App\Services\Playlist\PlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;

class UpdatePlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
        private readonly SongRepository $songRepository,
        private readonly PlaylistService $playlistService,
    ) {}

    /** @param User $user */
    public function __invoke(UpdatePlaylistRequest $request, Authenticatable $user)
    {
        $playlist = $this->playlistRepository->getOne($request->playlistId);
        $this->authorize('edit', $playlist);

        $changes = array_filter(
            ['name' => $request->name, 'description' => $request->comment],
            static fn ($value) => $value !== null,
        );

        if ($changes) {
            $playlist->update($changes);
        }

        if (($request->songIdToAdd || $request->songIndexToRemove) && $playlist->is_smart) {
            throw new OperationNotApplicableForSmartPlaylistException();
        }

        if ($request->songIdToAdd) {
            $songs = $this->songRepository->getMany($request->songIdToAdd);
            $this->playlistService->addPlayablesToPlaylist($playlist, $songs, $user);
        }

        if ($request->songIndexToRemove) {
            $songs = $playlist->load('playables')->playables->values();
            $toRemove = collect($request->songIndexToRemove)
                ->map($songs->get(...))
                ->filter()
                ->values();

            if ($toRemove->isNotEmpty()) {
                $this->playlistService->removePlayablesFromPlaylist($playlist, $toRemove);
            }
        }

        return SubsonicResponse::ok();
    }
}
