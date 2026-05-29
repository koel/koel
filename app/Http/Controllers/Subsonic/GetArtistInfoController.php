<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\ArtistRepository;
use App\Services\Contracts\Encyclopedia;

/**
 * Subsonic v1 `getArtistInfo`. Per spec the wrapper is `<artistInfo>` (v2 uses
 * `<artistInfo2>`); fields are otherwise identical for koel's purposes.
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

        if ($info === null) {
            return SubsonicResponse::ok(['artistInfo' => []]);
        }

        $imageUrl = $info->image ?: null;

        return SubsonicResponse::ok([
            'artistInfo' => [
                'biography' => $info->bio['summary'] ?: null,
                'lastFmUrl' => $info->url,
                'smallImageUrl' => $imageUrl,
                'mediumImageUrl' => $imageUrl,
                'largeImageUrl' => $imageUrl,
            ],
        ]);
    }
}
