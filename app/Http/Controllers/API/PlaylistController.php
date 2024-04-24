<?php

namespace App\Http\Controllers\API;

use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Facades\License;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistUpdateRequest;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistFolderRepository;
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
        private readonly PlaylistService $playlistService,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistFolderRepository $folderRepository,
        private readonly ?Authenticatable $user
    ) {
    }

    public function index()
    {
        return PlaylistResource::collection($this->playlistRepository->getAllAccessibleByUser($this->user));
    }

    public function store(PlaylistStoreRequest $request)
    {
        $folder = null;

        if ($request->folder_id) {
            $folder = $this->folderRepository->getOne($request->folder_id);
            $this->authorize('own', $folder);
        }

        try {
            $playlist = $this->playlistService->createPlaylist(
                $request->name,
                $this->user,
                $folder,
                Arr::wrap($request->songs),
                $request->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($request->rules)) : null,
                $request->own_songs_only && $request->rules && License::isPlus()
            );

            return PlaylistResource::make($playlist);
        } catch (PlaylistBothSongsAndRulesProvidedException $e) {
            throw ValidationException::withMessages(['songs' => [$e->getMessage()]]);
        }
    }

    public function update(PlaylistUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        $folder = null;

        if ($request->folder_id) {
            $folder = $this->folderRepository->getOne($request->folder_id);
            $this->authorize('own', $folder);
        }

        return PlaylistResource::make(
            $this->playlistService->updatePlaylist(
                $playlist,
                $request->name,
                $folder,
                $request->rules ? SmartPlaylistRuleGroupCollection::create(Arr::wrap($request->rules)) : null,
                $request->own_songs_only && $request->rules && License::isPlus()
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
