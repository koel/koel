<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Http\Requests\Download\DownloadSongsRequest;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class DownloadSongsController extends Controller
{
    public function __invoke(DownloadSongsRequest $request, DownloadService $service, SongRepository $repository)
    {
        // Don't use SongRepository::findMany() because it'd have been already catered to the current user.

        /** @var Array<Song>|Collection<array-key, Song> $songs */
        $songs = Song::query()->findMany($request->songs);
        $songs->each(fn ($song) => $this->authorize('download', $song));

        // For a single episode, we'll just redirect to its original media to save time and bandwidth
        if ($songs->count() === 1 && $songs[0]->isEpisode()) {
            return response()->redirectTo($songs[0]->path);
        }

        $downloadablePath = $service->getDownloadablePath($repository->getMany($request->songs));

        abort_unless((bool) $downloadablePath, Response::HTTP_BAD_REQUEST, 'Song or episode cannot be downloaded.');

        return response()->download($downloadablePath);
    }
}
