<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\Traits\Searchable;
use App\Values\Genre;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

class SongRepository extends Repository
{
    use Searchable;

    public const SORT_COLUMNS_NORMALIZE_MAP = [
        'title' => 'songs.title',
        'track' => 'songs.track',
        'length' => 'songs.length',
        'created_at' => 'songs.created_at',
        'disc' => 'songs.disc',
        'artist_name' => 'artists.name',
        'album_name' => 'albums.name',
    ];

    private const VALID_SORT_COLUMNS = [
        'songs.title',
        'songs.track',
        'songs.length',
        'songs.created_at',
        'artists.name',
        'albums.name',
    ];

    private const DEFAULT_QUEUE_LIMIT = 500;

    public function getOneByPath(string $path): ?Song
    {
        return Song::query()->where('path', $path)->first();
    }

    /** @return Collection|array<Song> */
    public function getAllHostedOnS3(): Collection
    {
        return Song::query()->hostedOnS3()->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRecentlyAdded(int $count = 10, ?User $scopedUser = null): Collection
    {
        return Song::query()->withMeta($scopedUser ?? $this->auth->user())->latest()->limit($count)->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getMostPlayed(int $count = 7, ?User $scopedUser = null): Collection
    {
        return Song::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->where('interactions.play_count', '>', 0)
            ->orderByDesc('interactions.play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRecentlyPlayed(int $count = 7, ?User $scopedUser = null): Collection
    {
        return Song::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->orderByDesc('interactions.last_played_at')
            ->limit($count)
            ->get();
    }

    public function getForListing(
        string $sortColumn,
        string $sortDirection,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        return self::applySort(
            Song::query()->withMeta($scopedUser ?? $this->auth->user()),
            $sortColumn,
            $sortDirection
        )
            ->simplePaginate($perPage);
    }

    public function getByGenre(
        string $genre,
        string $sortColumn,
        string $sortDirection,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        return self::applySort(
            Song::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->where('genre', $genre),
            $sortColumn,
            $sortDirection
        )
            ->simplePaginate($perPage);
    }

    /** @return Collection|array<array-key, Song> */
    public function getForQueue(
        string $sortColumn,
        string $sortDirection,
        int $limit = self::DEFAULT_QUEUE_LIMIT,
        ?User $scopedUser = null,
    ): Collection {
        return self::applySort(
            Song::query()->withMeta($scopedUser ?? $this->auth->user()),
            $sortColumn,
            $sortDirection
        )
            ->limit($limit)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getFavorites(?User $scopedUser = null): Collection
    {
        return Song::query()->withMeta($scopedUser ?? $this->auth->user())->where('interactions.liked', true)->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByAlbum(Album $album, ?User $scopedUser = null): Collection
    {
        return Song::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->where('album_id', $album->id)
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByArtist(Artist $artist, ?User $scopedUser = null): Collection
    {
        return Song::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->where('songs.artist_id', $artist->id)
            ->orWhere('albums.artist_id', $artist->id)
            ->orderBy('albums.name')
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByStandardPlaylist(Playlist $playlist, ?User $scopedUser = null): Collection
    {
        return Song::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->leftJoin('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
            ->leftJoin('playlists', 'playlists.id', '=', 'playlist_song.playlist_id')
            ->where('playlists.id', $playlist->id)
            ->orderBy('songs.title')
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRandom(int $limit, ?User $scopedUser = null): Collection
    {
        return Song::query()->withMeta($scopedUser ?? $this->auth->user())->inRandomOrder()->limit($limit)->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByIds(array $ids, ?User $scopedUser = null): Collection
    {
        return Song::query()->withMeta($scopedUser ?? $this->auth->user())->whereIn('songs.id', $ids)->get();
    }

    public function getOne($id, ?User $scopedUser = null): Song
    {
        return Song::query()->withMeta($scopedUser ?? $this->auth->user())->findOrFail($id);
    }

    public function count(): int
    {
        return Song::query()->count();
    }

    public function getTotalLength(): float
    {
        return Song::query()->sum('length');
    }

    private static function normalizeSortColumn(string $column): string
    {
        return key_exists($column, self::SORT_COLUMNS_NORMALIZE_MAP)
            ? self::SORT_COLUMNS_NORMALIZE_MAP[$column]
            : $column;
    }

    private static function applySort(Builder $query, string $column, string $direction): Builder
    {
        $column = self::normalizeSortColumn($column);

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        $query->orderBy($column, $direction);

        if ($column === 'artists.name') {
            $query->orderBy('albums.name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title');
        } elseif ($column === 'albums.name') {
            $query->orderBy('artists.name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title');
        } elseif ($column === 'track') {
            $query->orderBy('song.disc')
                ->orderBy('songs.track');
        }

        return $query;
    }

    /** @return Collection|array<array-key, Song> */
    public function getRandomByGenre(string $genre, int $limit, ?User $scopedUser = null): Collection
    {
        return Song::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->where('genre', $genre === Genre::NO_GENRE ? '' : $genre)
            ->limit($limit)
            ->inRandomOrder()
            ->get();
    }
}
