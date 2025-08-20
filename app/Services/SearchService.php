<?php

namespace App\Services;

use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\Contracts\ScoutableRepository;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Values\ExcerptSearchResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SearchService
{
    public const DEFAULT_EXCERPT_RESULT_LIMIT = 6;
    public const DEFAULT_SONG_RESULT_LIMIT = 500;

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly PodcastRepository $podcastRepository,
        private readonly RadioStationRepository $radioStationRepository,
    ) {
    }

    public function excerptSearch(
        string $keywords,
        int $limit = self::DEFAULT_EXCERPT_RESULT_LIMIT,
        ?User $scopedUser = null
    ): ExcerptSearchResult {
        $scopedUser ??= auth()->user();

        $results = [];

        /** @var ScoutableRepository $repository */
        foreach (
            [
                $this->songRepository,
                $this->artistRepository,
                $this->albumRepository,
                $this->podcastRepository,
                $this->radioStationRepository,
            ] as $repository
        ) {
            try {
                $results[] = $repository->search($keywords, $limit, $scopedUser);
            } catch (Throwable $e) {
                Log::error('Scout search failed', ['exception' => $e]);
                $results[] = new Collection();
            }
        }

        return ExcerptSearchResult::make(...$results);
    }

    /** @return Collection|array<array-key, Song> */
    public function searchSongs(
        string $keywords,
        ?User $scopedUser = null,
        int $limit = self::DEFAULT_SONG_RESULT_LIMIT
    ): Collection {
        return $this->songRepository->search($keywords, $limit, $scopedUser);
    }
}
