<?php

namespace App\Http\Controllers\API;

use App\Facades\License;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\AddSongsToPlaylistRequest;
use App\Http\Requests\API\RemoveSongsFromPlaylistRequest;
use App\Http\Resources\CollaborativeSongResource;
use App\Http\Resources\SongResource;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\PlaylistService;
use App\Services\SmartPlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class PlaylistSongController extends Controller
{
    /** @param User $user */
    public function __construct(
        private SongRepository $songRepository,
        private PlaylistService $playlistService,
        private SmartPlaylistService $smartPlaylistService,
        private ?Authenticatable $user
    ) {
    }

    public function index(Playlist $playlist)
    {
        if ($playlist->is_smart) {
            $this->authorize('own', $playlist);
            return SongResource::collection($this->smartPlaylistService->getSongs($playlist, $this->user));
        }

        $this->authorize('collaborate', $playlist);

        return self::createResourceCollection($this->songRepository->getByStandardPlaylist($playlist, $this->user));
    }

    public function store(Playlist $playlist, AddSongsToPlaylistRequest $request)
    {
        abort_if($playlist->is_smart, Response::HTTP_FORBIDDEN, 'Smart playlist content is automatically generated');

        $this->authorize('collaborate', $playlist);

        $songs = $this->songRepository->getMany(ids: $request->songs, scopedUser: $this->user);

        return self::createResourceCollection($this->playlistService->addSongsToPlaylist($playlist, $songs, $this->user));
    }

    private static function createResourceCollection(Collection $songs): ResourceCollection
    {
        return License::isPlus()
            ? CollaborativeSongResource::collection($songs)
            : SongResource::collection($songs);
    }

    public function destroy(Playlist $playlist, RemoveSongsFromPlaylistRequest $request)
    {
        abort_if($playlist->is_smart, Response::HTTP_FORBIDDEN, 'Smart playlist content is automatically generated');

        $this->authorize('collaborate', $playlist);
        $this->playlistService->removeSongsFromPlaylist($playlist, $request->songs);

        return response()->noContent();
    }
}
