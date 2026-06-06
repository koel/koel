<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\ArtistInfoResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\ArtistRepository;
use App\Services\Contracts\Encyclopedia;

/**
 * Subsonic v1 `getArtistInfo`. Per spec the wrapper is `<artistInfo>` (v2 uses
 * `<artistInfo2>`); the body shape is identical and shared via ArtistInfoResource.
 */
class GetArtistInfoController extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly Encyclopedia $encyclopedia,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $artist = $this->artistRepository->getOne($request->id);
        $info = $this->encyclopedia->getArtistInformation($artist);

        return SubsonicResponse::ok([
            'artistInfo' => $info === null ? [] : ArtistInfoResource::toArray($info),
        ]);
    }
}
