<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SongUpdateRequest;
use App\Models\Song;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Http\JsonResponse;

/**
 * @group 3. Song interactions
 */
class SongController extends Controller
{
    private $artistRepository;
    private $albumRepository;

    public function __construct(
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository
    ) {
        $this->artistRepository = $artistRepository;
        $this->albumRepository = $albumRepository;
    }

    /**
     * Update song information
     *
     * @bodyParam songs array required An array of song IDs to be updated.
     * @bodyParam data object required The new data, with these supported fields: `title`, `artistName`, `albumName`, and `lyrics`.
     *
     * @group 5. Media information
     *
     * @return JsonResponse
     */
    public function update(SongUpdateRequest $request)
    {
        $updatedSongs = Song::updateInfo($request->songs, $request->data);

        return response()->json([
            'artists' => $this->artistRepository->getByIds($updatedSongs->pluck('artist_id')->all()),
            'albums' => $this->albumRepository->getByIds($updatedSongs->pluck('album_id')->all()),
            'songs' => $updatedSongs,
        ]);
    }
}
