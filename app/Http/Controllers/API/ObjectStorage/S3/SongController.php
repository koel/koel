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
use Illuminate\Http\Response;

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

    public function put(PutSongRequest $request)
    {
        $path = "s3://{$request->bucket}/{$request->key}";

        $tags = $request->tags;
        $artist = Artist::getOrCreate(array_get($tags, 'artist'));

        $compilation = (bool) trim(array_get($tags, 'albumartist'));
        $album = Album::getOrCreate($artist, array_get($tags, 'album'), $compilation);

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

    public function remove(RemoveSongRequest $request)
    {
        $song = $this->songRepository->getOneByPath("s3://{$request->bucket}/{$request->key}");
        abort_unless((bool) $song, Response::HTTP_NOT_FOUND);

        $song->delete();
        event(new LibraryChanged());

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
