<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\ArtistRepository;
use App\Services\Contracts\Encyclopedia;

class GetArtistInfo2Controller extends Controller
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
            return SubsonicResponse::ok(['artistInfo2' => []]);
        }

        $imageUrl = $info->image ?: null;

        return SubsonicResponse::ok([
            'artistInfo2' => [
                'biography' => $info->bio['summary'] ?: null,
                'lastFmUrl' => $info->url,
                'smallImageUrl' => $imageUrl,
                'mediumImageUrl' => $imageUrl,
                'largeImageUrl' => $imageUrl,
            ],
        ]);
    }
}
