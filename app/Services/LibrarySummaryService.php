<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\GenreRepository;
use App\Repositories\SongRepository;
use App\Values\GenreSummary;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LibrarySummaryService
{
    private const string LIBRARY_CHANGED_AT_CACHE_KEY = 'library changed at';

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly GenreRepository $genreRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
    ) {}

    public function getSongCount(User $user): int
    {
        return $this->rememberSummaryForUser($user, 'song count', fn () => $this->songRepository->countSongs($user));
    }

    public function getAlbumCount(User $user): int
    {
        return $this->rememberSummaryForUser(
            $user,
            'album count',
            fn () => $this->albumRepository->countAccessibleByUser($user),
        );
    }

    public function getArtistCount(User $user): int
    {
        return $this->rememberSummaryForUser(
            $user,
            'artist count',
            fn () => $this->artistRepository->countAccessibleByUser($user),
        );
    }

    public function getTotalSongFileSize(User $user): int
    {
        return $this->rememberSummaryForUser(
            $user,
            'song file size',
            fn () => $this->songRepository->getTotalSongFileSize($user->organization),
        );
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

    public function markLibraryChanged(): void
    {
        Cache::forever(cache_key(self::LIBRARY_CHANGED_AT_CACHE_KEY), now()->getPreciseTimestamp());
    }

    public function getLibraryChangedAt(): mixed
    {
        return Cache::get(cache_key(self::LIBRARY_CHANGED_AT_CACHE_KEY));
    }

    /**
     * @template T
     * @param Closure(): T $compute
     * @return T
     */
    private function rememberSummaryForUser(User $user, string $summaryName, Closure $compute): mixed
    {
        $libraryChangedAt = $this->getLibraryChangedAt();

        $key = cache_key(
            'library summary',
            $user->id,
            $user->shouldIncludePublicMedia() ? 'with public media' : 'own media only',
            $summaryName,
        );

        $cached = Cache::get($key);

        if (is_array($cached) && $cached['library_changed_at'] === $libraryChangedAt) {
            return $cached['value'];
        }

        $value = $compute();

        Cache::put($key, ['library_changed_at' => $libraryChangedAt, 'value' => $value], now()->addDay());

        return $value;
    }
}
