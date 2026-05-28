<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\AlbumRepository;
use App\Services\Contracts\Encyclopedia;

class GetAlbumInfo2Controller extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly Encyclopedia $encyclopedia,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $album = $this->albumRepository->getOne($request->id);
        $info = $this->encyclopedia->getAlbumInformation($album);

        if ($info === null) {
            return SubsonicResponse::ok(['albumInfo' => []]);
        }

        $imageUrl = $info->cover ?: null;

        return SubsonicResponse::ok([
            'albumInfo' => [
                'notes' => $info->wiki['summary'] ?: null,
                'lastFmUrl' => $info->url,
                'smallImageUrl' => $imageUrl,
                'mediumImageUrl' => $imageUrl,
                'largeImageUrl' => $imageUrl,
            ],
        ]);
    }
}
