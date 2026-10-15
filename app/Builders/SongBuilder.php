<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Enums\ScanResultType;
use App\Facades\License;
use App\Models\Song;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Webmozart\Assert\Assert;

/**
 * @extends FavoriteableBuilder<Song>
 */
class SongBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const array SORT_COLUMNS_NORMALIZE_MAP = [
        'title' => 'songs.title',
        'track' => 'songs.track',
        'length' => 'songs.length',
        'created_at' => 'songs.created_at',
        'disc' => 'songs.disc',
        'year' => 'songs.year',
        'artist_name' => 'songs.artist_name',
        'album_name' => 'songs.album_name',
        'podcast_title' => 'podcasts.title',
        'podcast_author' => 'podcasts.author',
        'genre' => 'genres.name',
        'rating' => 'user_ratings.rating',
    ];

    private const array VALID_SORT_COLUMNS = [
        'songs.title',
        'songs.track',
        'songs.length',
        'songs.year',
        'songs.created_at',
        'songs.artist_name',
        'songs.album_name',
        'podcasts.title',
        'podcasts.author',
        'genres.name',
        'user_ratings.rating',
        'favorite',
    ];

    public function wherePath(string $path): self
    {
        return $this->where('songs.path_hash', simple_hash($path))->where('songs.path', $path);
    }

    /** @param array<string> $paths */
    public function wherePathIn(array $paths): self
    {
        return $this->whereIn('songs.path_hash', array_map(simple_hash(...), $paths))->whereIn('songs.path', $paths);
    }

    public function missingFromScan(string $scanId): self
    {
        $validFilesOfScan = static fn (QueryBuilder $query): QueryBuilder => $query
            ->from('scanned_files')
            ->where('scanned_files.scan_id', $scanId)
            ->whereIn('scanned_files.type', [ScanResultType::SUCCESS->value, ScanResultType::SKIPPED->value]);

        return $this->whereExists($validFilesOfScan)->whereNotExists(
            static fn (QueryBuilder $query): QueryBuilder => $validFilesOfScan($query)->whereColumn(
                'scanned_files.path_hash',
                'songs.path_hash',
            ),
        );
    }

    public function inDirectory(string $path): self
    {
        // Make sure the path ends with a directory separator.
        $path = Str::finish(trim($path), DIRECTORY_SEPARATOR);

        return $this->where('path', 'LIKE', "$path%");
    }

    public function withPlayCount(): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        return $this->leftJoin('interactions', function (JoinClause $join): void {
            $join->on('interactions.song_id', 'songs.id')->where('interactions.user_id', $this->user->id);
        })->addSelect(DB::raw('COALESCE(interactions.play_count, 0) as play_count'));
    }

    public function accessible(): self
    {
        if (License::isCommunity()) {
            // In the Community Edition, all songs are accessible by all users.
            return $this;
        }

        throw_unless($this->user, new LogicException('User must be set to query accessible songs.'));

        // We want to alias both podcasts and podcast_user tables to avoid possible conflicts with other joins.
        $this
            ->leftJoin('podcasts as podcasts_a11y', 'songs.podcast_id', 'podcasts_a11y.id')
            ->leftJoin('podcast_user as podcast_user_a11y', function (JoinClause $join): void {
                $join->on('podcasts_a11y.id', 'podcast_user_a11y.podcast_id')->where(
                    'podcast_user_a11y.user_id',
                    $this->user->id,
                );
            })
            ->whereNot(static function (self $query): void {
                // Episodes must belong to a podcast that the user is subscribed to.
                $query->whereNotNull('songs.podcast_id')->whereNull('podcast_user_a11y.podcast_id');
            });

        // If the song is a podcast episode, we need to ensure that the user has access to it.
        return $this->where(function (self $query): void {
            $query
                ->whereNotNull('songs.podcast_id')
                ->orWhere(function (self $q2) {
                    if (!$this->user->preferences->includePublicMedia) {
                        return $q2->whereBelongsTo($this->user, 'owner');
                    }

                    return $q2->where(function (self $q3): void {
                        $q3->whereBelongsTo($this->user, 'owner')->orWhere(function (self $q4): void {
                            $q4->where('songs.is_public', true)->whereHas('owner', fn (Builder $owner) => $owner->where(
                                'organization_id',
                                $this->user->organization_id,
                            )->where('owner_id', '<>', $this->user->id));
                        });
                    });
                });
        });
    }

    public function withUserContext(
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
        bool $includePlayCount = true,
    ): self {
        return $this
            ->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly))
            ->when($includePlayCount, static fn (self $query) => $query->withPlayCount())
            ->when(License::isPlus(), static fn (self $query) => $query->with('analysis:song_id,loudness,true_peak'))
            ->withRatingSubquery();
    }

    private function sortByOneColumn(string $column, string $direction): self
    {
        $column = self::SORT_COLUMNS_NORMALIZE_MAP[$column] ?? $column;

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        if ($column === 'user_ratings.rating') {
            return $this
                ->addSelect(DB::raw('(COALESCE(user_ratings.rating, 0)) as user_rating_score'))
                ->orderBy('user_rating_score', $direction)
                ->orderBy('songs.title');
        }

        if ($column === 'genres.name') {
            return $this
                ->addSelect(DB::raw(
                    "(COALESCE((SELECT MIN(genres.name) FROM genres INNER JOIN genre_song ON genre_song.genre_id = genres.id WHERE genre_song.song_id = songs.id), '')) as genre_sort_name",
                ))
                ->orderBy('genre_sort_name', $direction)
                ->orderBy('songs.title');
        }

        if ($column === 'songs.track') {
            return $this->orderBy('songs.disc', $direction)->orderBy('songs.track', $direction)->orderBy('songs.title');
        }

        return $this
            ->orderBy($column, $direction)
            // Depending on the column, we might need to order by other columns as well.
            ->when($column === 'songs.artist_name', static fn (self $query) => $query
                ->orderBy('songs.album_name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'))
            ->when($column === 'songs.album_name', static fn (self $query) => $query
                ->orderBy('songs.album_id')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'));
    }

    public function sort(array $columns, string $direction): self
    {
        $this->when(
            in_array('podcast_title', $columns, true) || in_array('podcast_author', $columns, true),
            static fn (self $query) => $query->leftJoin('podcasts', 'songs.podcast_id', 'podcasts.id'),
        )->when(in_array('rating', $columns, true), function (self $query): void {
            throw_unless($this->user, new LogicException('User must be set to sort by rating.'));

            $query->leftJoin('ratings as user_ratings', function (JoinClause $join): void {
                $join->on('user_ratings.rateable_id', 'songs.id')->where(
                    'user_ratings.rateable_type',
                    'playable',
                )->where('user_ratings.user_id', $this->user->id);
            });
        });

        foreach ($columns as $column) {
            $this->sortByOneColumn($column, $direction);
        }

        return $this;
    }

    public function genreless(): self
    {
        return $this->leftJoin('genre_song', 'genre_song.song_id', 'songs.id')->whereNull('genre_song.song_id');
    }

    public function storedLocally(): self
    {
        return $this->where(static function (self $query): void {
            $query->where(static function (self $q): void {
                $q->whereNull('songs.storage')->orWhere('songs.storage', '');
            })->whereNull('songs.podcast_id');
        });
    }
}
