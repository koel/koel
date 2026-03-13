<?php

namespace App\Ai\Services;

use App\Ai\AiRequestContext;
use App\Models\Song;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;

class SongRequestResolver
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    public function resolveSong(Request $request, AiRequestContext $context, string $queryKey = 'query'): ?Song
    {
        if (isset($request[$queryKey])) {
            return $this->songRepository->search($request[$queryKey], 1, $context->user)->first();
        }

        if ($context->currentSongId) {
            return $this->songRepository->findOne($context->currentSongId, $context->user);
        }

        return null;
    }

    /** @return Collection<int, Song> */
    public function resolveSongs(
        Request $request,
        AiRequestContext $context,
        string $queryKey = 'song_query',
        int $limit = 10,
    ): Collection {
        if (isset($request[$queryKey])) {
            return $this->songRepository->search($request[$queryKey], $limit, $context->user);
        }

        if ($context->currentSongId) {
            $song = $this->songRepository->findOne($context->currentSongId, $context->user);

            return $song ? Collection::make([$song]) : Collection::make();
        }

        return Collection::make();
    }
}
