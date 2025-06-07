<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Http\Requests\Download\DownloadSongsRequest;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Support\Collection;

class DownloadSongsController extends Controller
{
    public function __invoke(DownloadSongsRequest $request, DownloadService $service, SongRepository $repository)
    {
        // Don't use SongRepository::findMany() because it'd have been already catered to the current user.

        /** @var Array<Song>|Collection<array-key, Song> $songs */
        $songs = Song::query()->findMany($request->songs);
        $songs->each(fn ($song) => $this->authorize('download', $song));

        return $service->getDownloadable($repository->getMany($request->songs))?->toResponse();
    }
}
