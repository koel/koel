<?php

namespace App\Http\Controllers\API\ObjectStorage\S3;

use App\Exceptions\SongPathNotFoundException;
use App\Http\Requests\API\ObjectStorage\S3\PutSongRequest;
use App\Http\Requests\API\ObjectStorage\S3\RemoveSongRequest;
use App\Services\S3Service;
use Illuminate\Http\Response;

class SongController extends Controller
{
    private S3Service $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    public function put(PutSongRequest $request)
    {
        $song = $this->s3Service->createSongEntry(
            $request->bucket,
            $request->key,
            array_get($request->tags, 'artist'),
            array_get($request->tags, 'album'),
            (bool) trim(array_get($request->tags, 'albumartist')),
            array_get($request->tags, 'cover'),
            trim(array_get($request->tags, 'title', '')),
            (int) array_get($request->tags, 'duration', 0),
            (int) array_get($request->tags, 'track'),
            (string) array_get($request->tags, 'lyrics', '')
        );

        return response()->json($song);
    }

    public function remove(RemoveSongRequest $request)
    {
        try {
            $this->s3Service->deleteSongEntry($request->bucket, $request->key);
        } catch (SongPathNotFoundException $exception) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->noContent();
    }
}
