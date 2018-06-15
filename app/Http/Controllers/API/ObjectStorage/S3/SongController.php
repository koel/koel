<?php

namespace App\Http\Controllers\API\ObjectStorage\S3;

use App\Events\LibraryChanged;
use App\Http\Requests\API\ObjectStorage\S3\PutSongRequest;
use App\Http\Requests\API\ObjectStorage\S3\RemoveSongRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Exception;
use Illuminate\Http\JsonResponse;
use Media;

class SongController extends Controller
{
    /**
     * Store a new song or update an existing one with data from AWS.
     *
     * @param PutSongRequest $request
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
            $album->writeCoverFile(base64_decode($cover['data']), $cover['extension']);
        }

        $song = Song::updateOrCreate(['id' => Media::getHash($path)], [
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
     * Remove a song whose info matches with data sent from AWS.
     *
     * @param RemoveSongRequest $request
     *
     * @throws Exception
     *
     * @return JsonResponse
     */
    public function remove(RemoveSongRequest $request)
    {
        $song = Song::byPath("s3://{$request->bucket}/{$request->key}");
        abort_unless((bool) $song, 404);
        $song->delete();
        event(new LibraryChanged());

        return response()->json();
    }
}
