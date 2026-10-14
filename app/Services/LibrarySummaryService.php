<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Repositories\GenreRepository;
use App\Repositories\SongRepository;
use App\Values\GenreSummary;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LibrarySummaryService
{
    private const string LIBRARY_CHANGED_AT_CACHE_KEY = 'library changed at';

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly GenreRepository $genreRepository,
        private readonly ArtistRepository $artistRepository,
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

    /** @return EloquentCollection<int, Artist> */
    public function getAlbumArtistsWithoutFavoritesOrRatings(User $user): EloquentCollection
    {
        $attributesOfArtists = $this->rememberSummaryForUser($user, 'album artists', fn () => $this->artistRepository
            ->getAllAlbumArtistsWithoutFavoritesOrRatings($user)
            ->map(static fn (Artist $artist) => $artist->getAttributes())
            ->all());

        return Artist::hydrate($attributesOfArtists);
    }

    public function markLibraryChanged(): void
    {
        Cache::forever(cache_key(self::LIBRARY_CHANGED_AT_CACHE_KEY), now()->getPreciseTimestamp());
    }

    /**
     * @template T
     * @param Closure(): T $compute
     * @return T
     */
    private function rememberSummaryForUser(User $user, string $summaryName, Closure $compute): mixed
    {
        $libraryChangedAt = Cache::get(cache_key(self::LIBRARY_CHANGED_AT_CACHE_KEY));

        $key = cache_key(
            'library summary',
            $user->id,
            $user->preferences->includePublicMedia ? 'with public media' : 'own media only',
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
