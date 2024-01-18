<?php

namespace App\Http\Controllers\API\ObjectStorage\S3;

use App\Exceptions\SongPathNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ObjectStorage\S3\PutSongRequest;
use App\Http\Requests\API\ObjectStorage\S3\RemoveSongRequest;
use App\Services\S3Service;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class SongController extends Controller
{
    public function __construct(private S3Service $s3Service)
    {
    }

    public function put(PutSongRequest $request)
    {
        $artist = Arr::get($request->tags, 'artist', '');

        $song = $this->s3Service->createSongEntry(
            $request->bucket,
            $request->key,
            $artist,
            Arr::get($request->tags, 'album'),
            trim(Arr::get($request->tags, 'albumartist')),
            Arr::get($request->tags, 'cover'),
            trim(Arr::get($request->tags, 'title', '')),
            (int) Arr::get($request->tags, 'duration', 0),
            (int) Arr::get($request->tags, 'track'),
            (string) Arr::get($request->tags, 'lyrics', '')
        );

        return response()->json($song);
    }

    public function remove(RemoveSongRequest $request)
    {
        try {
            $this->s3Service->deleteSongEntry($request->bucket, $request->key);
        } catch (SongPathNotFoundException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->noContent();
    }
}
