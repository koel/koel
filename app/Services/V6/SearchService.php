<?php

namespace App\Services\V6;

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
            $this->songRepository->getByIds(
                Song::search($keywords)->get()->take($count)->pluck('id')->all(),
                $scopedUser
            ),
            $this->artistRepository->getByIds(
                Artist::search($keywords)->get()->take($count)->pluck('id')->all(),
                $scopedUser
            ),
            $this->albumRepository->getByIds(
                Album::search($keywords)->get()->take($count)->pluck('id')->all(),
                $scopedUser
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
                $builder->withMeta($scopedUser ?? auth()->user())->limit($limit);
            })
            ->get();
    }
}
