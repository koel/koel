<?php

namespace App\Services;

use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;

class SearchService
{
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
    public function excerptSearch(string $keywords): array
    {
        return [
            'songs' => self::getTopResults($this->songRepository->search($keywords)),
            'artists' => self::getTopResults($this->artistRepository->search($keywords)),
            'albums' => self::getTopResults($this->albumRepository->search($keywords)),
        ];
    }

    /** @return Collection|array<Model> */
    private static function getTopResults(Builder $query, int $count = 6): Collection
    {
        return $query->take($count)->get();
    }
}
