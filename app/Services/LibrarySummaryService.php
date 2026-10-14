<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\GenreRepository;
use App\Repositories\SongRepository;
use App\Values\GenreSummary;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LibrarySummaryService
{
    private const string VERSION_CACHE_KEY = 'library summary version';

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly GenreRepository $genreRepository,
    ) {}

    public function getSongCount(User $user): int
    {
        return $this->rememberSummaryForUser($user, 'song count', fn () => $this->songRepository->countSongs($user));
    }

    public function getTotalSongLength(User $user): float
    {
        return $this->rememberSummaryForUser($user, 'song length', fn () => $this->songRepository->getTotalSongLength(
            $user,
        ));
    }

    /** @return Collection<int, GenreSummary> */
    public function getGenreSummaries(User $user): Collection
    {
        return $this->rememberSummaryForUser($user, 'genre summaries', fn () => $this->genreRepository->getAllSummaries(
            $user,
        ));
    }

    public function invalidateSummaries(): void
    {
        Cache::forever(cache_key(self::VERSION_CACHE_KEY), Str::random());
    }

    /**
     * @template T
     * @param Closure(): T $compute
     * @return T
     */
    private function rememberSummaryForUser(User $user, string $summaryName, Closure $compute): mixed
    {
        $version = Cache::rememberForever(cache_key(self::VERSION_CACHE_KEY), Str::random(...));

        $key = cache_key(
            'library summary',
            $version,
            $user->id,
            $user->preferences->includePublicMedia ? 'with public media' : 'own media only',
            $summaryName,
        );

        return Cache::remember($key, now()->addDay(), $compute);
    }
}
