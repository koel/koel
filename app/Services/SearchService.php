<?php

namespace App\Services;

use App\Builders\SongBuilder;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Values\ExcerptSearchResult;
use Illuminate\Support\Collection;

class SearchService
{
    public const DEFAULT_EXCERPT_RESULT_COUNT = 6;
    public const DEFAULT_MAX_SONG_RESULT_COUNT = 500;

    public function __construct(
        private SongRepository $songRepository,
        private AlbumRepository $albumRepository,
        private ArtistRepository $artistRepository
    ) {
    }

    public function excerptSearch(
        string $keywords,
        ?User $scopedUser = null,
        int $count = self::DEFAULT_EXCERPT_RESULT_COUNT
    ): ExcerptSearchResult {
        $scopedUser ??= auth()->user();

        return ExcerptSearchResult::make(
            $this->songRepository->getMany(
                ids: Song::search($keywords)->get()->take($count)->pluck('id')->all(),
                inThatOrder: true,
                scopedUser: $scopedUser
            ),
            $this->artistRepository->getMany(Artist::search($keywords)->get()->take($count)->pluck('id')->all(), true),
            $this->albumRepository->getMany(Album::search($keywords)->get()->take($count)->pluck('id')->all(), true),
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
