<?php

namespace App\Services;

use App\Builders\SongBuilder;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PodcastRepository;
use App\Repositories\Repository;
use App\Repositories\SongRepository;
use App\Values\ExcerptSearchResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SearchService
{
    public const DEFAULT_EXCERPT_RESULT_COUNT = 6;
    public const DEFAULT_MAX_SONG_RESULT_COUNT = 500;

    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly PodcastRepository $podcastRepository
    ) {
    }

    public function excerptSearch(
        string $keywords,
        int $count = self::DEFAULT_EXCERPT_RESULT_COUNT
    ): ExcerptSearchResult {
        return ExcerptSearchResult::make(
            self::excerptScoutSearch($keywords, $count, $this->songRepository),
            self::excerptScoutSearch($keywords, $count, $this->artistRepository),
            self::excerptScoutSearch($keywords, $count, $this->albumRepository),
            self::excerptScoutSearch($keywords, $count, $this->podcastRepository),
        );
    }

    /**
     * @param SongRepository|AlbumRepository|ArtistRepository|PodcastRepository $repository
     *
     * @return Collection|array<array-key, Song|Artist|Album|Podcast>
     */
    private static function excerptScoutSearch(string $keywords, int $count, Repository $repository): Collection
    {
        try {
            return $repository->getMany(
                ids: $repository->model::search($keywords)->get()->take($count)->pluck('id')->all(), // @phpstan-ignore-line
                preserveOrder: true,
            );
        } catch (Throwable $e) {
            Log::error('Scout search failed', ['exception' => $e]);

            return new Collection();
        }
    }

    /** @return Collection|array<array-key, Song> */
    public function searchSongs(
        string $keywords,
        ?User $scopedUser = null,
        int $limit = self::DEFAULT_MAX_SONG_RESULT_COUNT
    ): Collection {
        return Song::search($keywords)
            ->query(
                static fn (SongBuilder $builder) => $builder
                    ->forUser($scopedUser ?? auth()->user())
                    ->withMeta()
                    ->limit($limit)
            )
            ->get();
    }
}
