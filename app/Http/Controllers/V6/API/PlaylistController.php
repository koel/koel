<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistUpdateRequest;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\User;
use App\Services\PlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

class PlaylistController extends Controller
{
    /** @param User $user */
    public function __construct(private PlaylistService $playlistService, private ?Authenticatable $user)
    {
    }

    public function index()
    {
        return PlaylistResource::collection($this->user->playlists);
    }

    public function store(PlaylistStoreRequest $request)
    {
        $playlist = $this->playlistService->createPlaylist(
            $request->name,
            $this->user,
            Arr::wrap($request->songs),
            $request->rules
        );

        return PlaylistResource::make($playlist);
    }

    public function update(PlaylistUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        return PlaylistResource::make(
            $this->playlistService->updatePlaylist(
                $playlist,
                $request->name,
                Arr::wrap($request->rules)
            )
        );
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        $playlist->delete();

        return response()->noContent();
    }
}
