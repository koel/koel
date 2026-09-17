<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\AlbumInfoResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\AlbumRepository;
use App\Services\Integrations\EncyclopediaService;
use stdClass;

class GetAlbumInfo2Controller extends Controller
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly EncyclopediaService $encyclopedia,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $album = $this->albumRepository->getOne($request->id);
        $info = $this->encyclopedia->getAlbumInformation($album);

        return SubsonicResponse::ok([
            'albumInfo' => $info ? AlbumInfoResource::toArray($info) : new stdClass(),
        ]);
    }
}
