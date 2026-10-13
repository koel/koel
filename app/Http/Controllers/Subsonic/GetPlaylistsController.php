<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\PlaylistResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetPlaylistsController extends Controller
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $playlists = $this->playlistRepository
            ->getAllAccessibleByUser($user)
            ->loadCount('playables')
            ->loadSum('playables', 'length');

        $playlists
            ->where('is_smart', true)
            ->each(function (Playlist $playlist) use ($user): void {
                $stats = $this->songRepository->getSmartPlaylistStats($playlist, $user);

                $playlist->setAttribute('playables_count', $stats['count'])->setAttribute(
                    'playables_sum_length',
                    $stats['length'],
                );
            });

        return SubsonicResponse::ok([
            'playlists' => [
                'playlist' => $playlists->map(PlaylistResource::toArray(...))->all(),
            ],
        ]);
    }
}
