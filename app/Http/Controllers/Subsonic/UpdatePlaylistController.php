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
use Illuminate\Support\Arr;

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
            [
                'name' => $request->input('name'),
                'description' => $request->input('comment'),
            ],
            static fn ($value) => $value !== null,
        );

        if ($changes) {
            $playlist->update($changes);
        }

        $songIdsToAdd = Arr::wrap($request->input('songIdToAdd', []));
        $indicesToRemove = Arr::wrap($request->input('songIndexToRemove', []));

        if (($songIdsToAdd || $indicesToRemove) && $playlist->is_smart) {
            throw new OperationNotApplicableForSmartPlaylistException();
        }

        if ($songIdsToAdd) {
            $songs = $this->songRepository->getMany($songIdsToAdd);
            $this->playlistService->addPlayablesToPlaylist($playlist, $songs, $user);
        }

        if ($indicesToRemove) {
            $songs = $playlist->load('playables')->playables->values();
            $toRemove = collect($indicesToRemove)
                ->map(static fn (int|string $index) => $songs->get((int) $index))
                ->filter()
                ->values();

            if ($toRemove->isNotEmpty()) {
                $this->playlistService->removePlayablesFromPlaylist($playlist, $toRemove);
            }
        }

        return SubsonicResponse::ok();
    }
}
