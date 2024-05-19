<?php

namespace App\Services;

use App\Builders\SongBuilder;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Podcast\Podcast;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PodcastRepository;
use App\Repositories\SongRepository;
use App\Values\ExcerptSearchResult;
use Illuminate\Support\Collection;

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
        ?User $scopedUser = null,
        int $count = self::DEFAULT_EXCERPT_RESULT_COUNT
    ): ExcerptSearchResult {
        $scopedUser ??= auth()->user();

        return ExcerptSearchResult::make(
            songs: $this->songRepository->getMany(
                ids: Song::search($keywords)->get()->take($count)->pluck('id')->all(),
                inThatOrder: true,
                scopedUser: $scopedUser
            ),
            artists: $this->artistRepository->getMany(
                ids: Artist::search($keywords)->get()->take($count)->pluck('id')->all(),
                inThatOrder: true
            ),
            albums: $this->albumRepository->getMany(
                ids: Album::search($keywords)->get()->take($count)->pluck('id')->all(),
                inThatOrder: true
            ),
            podcasts: $this->podcastRepository->getMany(
                ids: Podcast::search($keywords)->get()->take($count)->pluck('id')->all(),
                inThatOrder: true
            ),
        );
    }

    /** @return Collection|array<array-key, Song> */
    public function searchSongs(
        string $keywords,
        ?User $scopedUser = null,
        int $limit = self::DEFAULT_MAX_SONG_RESULT_COUNT
    ): Collection {
        return Song::search($keywords)
            ->query(static function (SongBuilder $builder) use ($scopedUser, $limit): void {
                $builder->withMetaFor($scopedUser ?? auth()->user())->limit($limit);
            })
            ->get();
    }
}
