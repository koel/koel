<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetSongController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(IdRequest $request, Authenticatable $user)
    {
        $song = $this->songRepository->getOne($request->id);

        return SubsonicResponse::ok([
            'song' => SongResource::toArray($song, $user),
        ]);
    }
}
