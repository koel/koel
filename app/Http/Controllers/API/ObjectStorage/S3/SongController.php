<?php

namespace App\Http\Controllers\API\ObjectStorage\S3;

use App\Events\LibraryChanged;
use App\Http\Requests\API\ObjectStorage\S3\PutSongRequest;
use App\Http\Requests\API\ObjectStorage\S3\RemoveSongRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\HelperService;
use App\Services\MediaMetadataService;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * @group AWS integration
 *
 * These routes are meant for Amazon Web Services (AWS) integration with Koel. For more information, visit
 * [koel-aws](https://github.com/koel/koel-aws).
 */
class SongController extends Controller
{
    private $mediaMetadataService;
    private $songRepository;
    private $helperService;

    public function __construct(
        MediaMetadataService $mediaMetadataService,
        HelperService $helperService,
        SongRepository $songRepository
    ) {
        $this->mediaMetadataService = $mediaMetadataService;
        $this->songRepository = $songRepository;
        $this->helperService = $helperService;
    }

    /**
     * Store a song
     *
     * Create a new song or update an existing one with data sent from AWS.
     *
     * @return JsonResponse
     */
    public function put(PutSongRequest $request)
    {
        $path = "s3://{$request->bucket}/{$request->key}";

        $tags = $request->tags;
        $artist = Artist::get(array_get($tags, 'artist'));

        $compilation = (bool) trim(array_get($tags, 'albumartist'));
        $album = Album::get($artist, array_get($tags, 'album'), $compilation);

        if ($cover = array_get($tags, 'cover')) {
            $this->mediaMetadataService->writeAlbumCover($album, base64_decode($cover['data']), $cover['extension']);
        }

        $song = Song::updateOrCreate(['id' => $this->helperService->getFileHash($path)], [
            'path' => $path,
            'album_id' => $album->id,
            'artist_id' => $artist->id,
            'title' => trim(array_get($tags, 'title', '')),
            'length' => array_get($tags, 'duration', 0) ?: 0,
            'track' => (int) array_get($tags, 'track'),
            'lyrics' => array_get($tags, 'lyrics', '') ?: '',
            'mtime' => time(),
        ]);

        event(new LibraryChanged());

        return response()->json($song);
    }

    /**
     * Remove a song
     *
     * Remove a song whose information matches with data sent from AWS.
     *
     * @throws Exception
     *
     * @return JsonResponse
     */
    public function remove(RemoveSongRequest $request)
    {
        $song = $this->songRepository->getOneByPath("s3://{$request->bucket}/{$request->key}");
        abort_unless((bool) $song, 404);

        $song->delete();
        event(new LibraryChanged());

        return response()->json();
    }
}
