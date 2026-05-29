<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\IndexesResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\ArtistRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetIndexesController extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $artists = $this->artistRepository->getAll();

        return SubsonicResponse::ok([
            'indexes' => IndexesResource::toArray($artists, $user)
                + [
                    'lastModified' => (int) ($artists->max('updated_at')?->getTimestampMs() ?? now()->getTimestampMs()),
                ],
        ]);
    }
}
