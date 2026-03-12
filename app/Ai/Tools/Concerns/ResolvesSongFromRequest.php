<?php

namespace App\Ai\Tools\Concerns;

use App\Models\Song;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;

trait ResolvesSongFromRequest
{
    private function resolveSong(Request $request, string $queryKey = 'query'): ?Song
    {
        if (isset($request[$queryKey])) {
            return $this->songRepository->search($request[$queryKey], 1, $this->context->user)->first();
        }

        if ($this->context->currentSongId) {
            return $this->songRepository->findOne($this->context->currentSongId, $this->context->user);
        }

        return null;
    }

    /** @return Collection<int, Song> */
    private function resolveSongs(Request $request, string $queryKey = 'song_query', int $limit = 10): Collection
    {
        if (isset($request[$queryKey])) {
            return $this->songRepository->search($request[$queryKey], $limit, $this->context->user);
        }

        if ($this->context->currentSongId) {
            $song = $this->songRepository->findOne($this->context->currentSongId, $this->context->user);

            return $song ? new Collection([$song]) : new Collection();
        }

        return new Collection();
    }
}
