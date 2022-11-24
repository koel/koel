<?php

namespace App\Http\Controllers\API;

use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistUpdateRequest;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Services\PlaylistService;
use App\Values\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PlaylistController extends Controller
{
    /** @param User $user */
    public function __construct(
        private PlaylistRepository $playlistRepository,
        private PlaylistService $playlistService,
        private ?Authenticatable $user
    ) {
    }

    public function index()
    {
        return response()->json($this->playlistRepository->getAllByCurrentUser());
    }

    public function store(PlaylistStoreRequest $request)
    {
        try {
            $playlist = $this->playlistService->createPlaylist(
                $request->name,
                $this->user,
                null,
                Arr::wrap($request->songs),
                $request->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($request->rules)) : null
            );

            $playlist->songs = $playlist->songs->pluck('id')->toArray();

            return response()->json($playlist);
        } catch (PlaylistBothSongsAndRulesProvidedException $e) {
            throw ValidationException::withMessages(['songs' => [$e->getMessage()]]);
        }
    }

    public function update(PlaylistUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        $playlist->update($request->only('name', 'rules'));

        return response()->json($playlist);
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        $playlist->delete();

        return response()->noContent();
    }
}
