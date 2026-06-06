<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\PlaylistRepository;

class DeletePlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $playlist = $this->playlistRepository->getOne($request->id);
        $this->authorize('delete', $playlist);

        $playlist->delete();

        return SubsonicResponse::ok();
    }
}
