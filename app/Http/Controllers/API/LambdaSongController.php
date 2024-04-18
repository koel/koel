<?php

namespace App\Http\Controllers\API;

use App\Exceptions\SongPathNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ObjectStorage\S3\PutSongRequest;
use App\Http\Requests\API\ObjectStorage\S3\RemoveSongRequest;
use App\Services\SongStorages\S3LambdaStorage;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class LambdaSongController extends Controller
{
    public function __construct(private readonly S3LambdaStorage $storage)
    {
    }

    public function put(PutSongRequest $request)
    {
        $artist = Arr::get($request->tags, 'artist', '');

        $song = $this->storage->createSongEntry(
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
            $this->storage->deleteSongEntry($request->bucket, $request->key);
        } catch (SongPathNotFoundException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->noContent();
    }
}
