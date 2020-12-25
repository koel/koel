<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;

class SearchService
{
    public const DEFAULT_EXCERPT_RESULT_COUNT = 6;

    private $songRepository;
    private $albumRepository;
    private $artistRepository;

    public function __construct(
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
        ArtistRepository $artistRepository
    ) {
        $this->songRepository = $songRepository;
        $this->albumRepository = $albumRepository;
        $this->artistRepository = $artistRepository;
    }

    /** @return array<mixed> */
    public function excerptSearch(string $keywords, int $count): array
    {
        return [
            'songs' => self::getTopResults($this->songRepository->search($keywords), $count)
                ->map(static function (Song $song): string {
                    return $song->id;
                }),
            'artists' => self::getTopResults($this->artistRepository->search($keywords), $count)
                ->map(static function (Artist $artist): int {
                    return $artist->id;
                }),
            'albums' => self::getTopResults($this->albumRepository->search($keywords), $count)
                ->map(static function (Album $album): int {
                    return $album->id;
                }),
        ];
    }

    /** @return Collection|array<Model> */
    private static function getTopResults(Builder $query, int $count): Collection
    {
        return $query->take($count)->get();
    }

    /** @return Collection|array<string> */
    public function searchSongs(string $keywords): Collection
    {
        return $this->songRepository
            ->search($keywords)
            ->get()
            ->map(static function (Song $song): string {
                return $song->id;
            });
    }
}
