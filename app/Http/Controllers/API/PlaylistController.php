<?php

namespace App\Http\Controllers\API;

use App\Exceptions\PlaylistBothSongsAndRulesProvidedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Playlist\PlaylistStoreRequest;
use App\Http\Requests\API\Playlist\PlaylistUpdateRequest;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistFolderRepository;
use App\Repositories\PlaylistRepository;
use App\Services\PlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

class PlaylistController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly PlaylistService $playlistService,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistFolderRepository $folderRepository,
        private readonly Authenticatable $user
    ) {
    }

    public function index()
    {
        return PlaylistResource::collection($this->playlistRepository->getAllAccessibleByUser($this->user));
    }

    public function store(PlaylistStoreRequest $request)
    {
        if ($request->folder_id) {
            $this->authorize('own', $this->folderRepository->getOne($request->folder_id));
        }

        try {
            $playlist = $this->playlistService->createPlaylist($request->toDto(), $this->user);

            return PlaylistResource::make($playlist);
        } catch (PlaylistBothSongsAndRulesProvidedException $e) {
            throw ValidationException::withMessages(['songs' => [$e->getMessage()]]);
        }
    }

    public function update(PlaylistUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        if ($request->folder_id) {
            $this->authorize('own', $this->folderRepository->getOne($request->folder_id));
        }

        return PlaylistResource::make($this->playlistService->updatePlaylist($playlist, $request->toDto()));
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        $playlist->delete();

        return response()->noContent();
    }
}
