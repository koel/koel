<?php

namespace App\Http\Controllers\API;

use App\Factories\StreamerFactory;
use App\Http\Requests\API\SongPlayRequest;
use App\Http\Requests\API\SongUpdateRequest;
use App\Models\Song;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Services\MediaInformationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class SongController extends Controller
{
    private $mediaInformationService;
    private $streamerFactory;
    private $artistRepository;
    private $albumRepository;

    public function __construct(
        MediaInformationService $mediaInformationService,
        StreamerFactory $streamerFactory,
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository
    ) {
        $this->mediaInformationService = $mediaInformationService;
        $this->streamerFactory = $streamerFactory;
        $this->artistRepository = $artistRepository;
        $this->albumRepository = $albumRepository;
    }

    /**
     * Play/stream a song.
     *
     * @link https://github.com/phanan/koel/wiki#streaming-music
     *
     * @param null|bool $transcode Whether to force transcoding the song.
     *                             If this is omitted, by default Koel will transcode FLAC.
     * @param null|int  $bitRate   The target bit rate to transcode, defaults to OUTPUT_BIT_RATE.
     *                             Only taken into account if $transcode is truthy.
     *
     * @return RedirectResponse|Redirector
     */
    public function play(SongPlayRequest $request, Song $song, ?bool $transcode = null, ?int $bitRate = null)
    {
        return $this->streamerFactory
            ->createStreamer($song, $transcode, $bitRate, floatval($request->time))
            ->stream();
    }

    /**
     * Update songs info.
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
