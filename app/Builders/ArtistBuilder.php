<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use LogicException;
use Webmozart\Assert\Assert;

/**
 * @extends FavoriteableBuilder<Artist>
 */
class ArtistBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const array SORT_COLUMNS_NORMALIZE_MAP = [
        'name' => 'artists.name',
        'created_at' => 'artists.created_at',
        'rating' => 'rating',
    ];

    private const array VALID_SORT_COLUMNS = [
        'artists.name',
        'artists.created_at',
        'favorite', // alias column for favorite status
        'rating', // alias column for the requesting user's rating
    ];

    public function onlyStandard(): self
    {
        return $this->whereNotIn('artists.name', [Artist::UNKNOWN_NAME, Artist::VARIOUS_NAME]);
    }

    public function onlyAlbumArtists(): self
    {
        return $this->whereHas('albums');
    }

    /**
     * Count the artist's own albums plus the albums (e.g., compilations) they appear on, as `albums_count`.
     */
    public function withAlbumCountIncludingAppearances(): self
    {
        $user = $this->user;

        $ownAlbums = DB::query()
            ->selectRaw('COUNT(*)')
            ->from('albums')
            ->when($user, static fn (QueryBuilder $query) => AlbumBuilder::limitToAlbumsAccessibleByUser($query, $user))
            ->whereColumn('albums.artist_id', 'artists.id');

        $appearedOnAlbums = DB::getDriverName() === 'pgsql'
            ? self::countAlbumsAppearedOnByAlbumIdLookup($user)
            : self::countAlbumsAppearedOnByJoiningSongs($user);

        return $this->selectRaw(
            sprintf('(%s) + (%s) as albums_count', $ownAlbums->toSql(), $appearedOnAlbums->toSql()),
            [...$ownAlbums->getBindings(), ...$appearedOnAlbums->getBindings()],
        );
    }

    private static function countAlbumsAppearedOnByJoiningSongs(?User $user): QueryBuilder
    {
        return DB::query()
            ->selectRaw('COUNT(DISTINCT albums.id)')
            ->from('songs as appearance_songs')
            ->join('albums', 'albums.id', 'appearance_songs.album_id')
            ->when($user, static fn (QueryBuilder $query) => AlbumBuilder::limitToAlbumsAccessibleByUser($query, $user))
            ->whereColumn('appearance_songs.artist_id', 'artists.id')
            ->whereColumn('albums.artist_id', '<>', 'artists.id');
    }

    private static function countAlbumsAppearedOnByAlbumIdLookup(?User $user): QueryBuilder
    {
        return DB::query()
            ->selectRaw('COUNT(*)')
            ->from('albums')
            ->when($user, static fn (QueryBuilder $query) => AlbumBuilder::limitToAlbumsAccessibleByUser($query, $user))
            ->whereIn('albums.id', static function (QueryBuilder $appearanceSongs): void {
                $appearanceSongs
                    ->select('appearance_songs.album_id')
                    ->from('songs as appearance_songs')
                    ->whereColumn('appearance_songs.artist_id', 'artists.id');
            })
            ->whereColumn('albums.artist_id', '<>', 'artists.id');
    }

    public function whereAccessibleByUser(User $user): self
    {
        $this->user = $user;

        return $this->accessible();
    }

    public function accessible(): self
    {
        if (License::isCommunity()) {
            return $this;
        }

        throw_unless($this->user, new LogicException('User must be set to query accessible artists.'));

        if (!$this->user->shouldIncludePublicMedia()) {
            return $this->whereBelongsTo($this->user);
        }

        return $this->where(function (Builder $query): void {
            $query
                ->whereBelongsTo($this->user)
                ->orWhereExists(function (QueryBuilder $sub): void {
                    $sub
                        ->select(DB::raw(1))
                        ->from('songs')
                        ->whereColumn('songs.artist_id', 'artists.id')
                        ->tap($this->limitToSongsSharedWithUser(...));
                })
                ->orWhereExists(function (QueryBuilder $sub): void {
                    $sub
                        ->select(DB::raw(1))
                        ->from('albums')
                        ->join('songs', 'songs.album_id', 'albums.id')
                        ->whereColumn('albums.artist_id', 'artists.id')
                        ->tap($this->limitToSongsSharedWithUser(...));
                });
        });
    }

    private function limitToSongsSharedWithUser(QueryBuilder $query): void
    {
        $query
            ->join('users', 'songs.owner_id', 'users.id')
            ->where('songs.is_public', true)
            ->where('users.organization_id', $this->user->organization_id)
            ->where('songs.owner_id', '<>', $this->user->id);
    }

    public function withPlayCount(bool $playedOnly = false): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        $playCountsByArtist = DB::table('interactions')
            ->join('songs as songs_for_playcount', 'songs_for_playcount.id', 'interactions.song_id')
            ->where('interactions.user_id', $this->user->id)
            ->where('interactions.play_count', '>', 0)
            ->groupBy('songs_for_playcount.artist_id')
            ->select('songs_for_playcount.artist_id')
            ->selectRaw('SUM(interactions.play_count) as play_count');

        return $this->joinSub(
            $playCountsByArtist,
            'artist_play_counts',
            'artist_play_counts.artist_id',
            '=',
            'artists.id',
            $playedOnly ? 'inner' : 'left',
        )->addSelect(DB::raw('COALESCE(artist_play_counts.play_count, 0) as play_count'));
    }

    public function sort(string $column, string $direction): self
    {
        $column = self::SORT_COLUMNS_NORMALIZE_MAP[$column] ?? $column;

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        return $this->orderBy($column, $direction);
    }

    /**
     * For artists without an image, select the cover of the scoped user's most played album as `album_cover`,
     * falling back to the most recently added album with a cover.
     */
    private function withAlbumCover(): self
    {
        $user = $this->user;

        return $this->addSelect(['album_cover' => static function (QueryBuilder $query) use ($user): void {
            $query
                ->select('albums.cover')
                ->tap(static fn (QueryBuilder $albums) => self::fromAlbumCoversOfArtistWithoutImage($albums, $user))
                ->leftJoin('songs as album_cover_songs', 'album_cover_songs.album_id', 'albums.id')
                ->leftJoin('interactions as album_cover_interactions', static function (JoinClause $join) use (
                    $user,
                ): void {
                    $join->on('album_cover_interactions.song_id', 'album_cover_songs.id')->where(
                        'album_cover_interactions.user_id',
                        $user->id,
                    );
                })
                ->groupBy('albums.id', 'albums.cover', 'albums.created_at')
                ->orderByRaw('COALESCE(SUM(album_cover_interactions.play_count), 0) DESC')
                ->orderByDesc('albums.created_at')
                ->limit(1);
        }]);
    }

    /**
     * Select whether the artist would get an `album_cover`, as `has_album_cover`, without ranking albums to pick one.
     */
    public function withAlbumCoverPresence(): self
    {
        $user = $this->user;

        return $this->addSelect(['has_album_cover' => static function (QueryBuilder $query) use ($user): void {
            $query
                ->selectRaw('1')
                ->tap(static fn (QueryBuilder $albums) => self::fromAlbumCoversOfArtistWithoutImage($albums, $user))
                ->limit(1);
        }]);
    }

    private static function fromAlbumCoversOfArtistWithoutImage(QueryBuilder $query, User $user): void
    {
        $query
            ->from('albums')
            ->tap(static fn (QueryBuilder $albums) => AlbumBuilder::limitToAlbumsAccessibleByUser($albums, $user))
            ->whereColumn('albums.artist_id', 'artists.id')
            ->where('albums.cover', '<>', '')
            ->where(static function (QueryBuilder $withoutImage): void {
                $withoutImage->whereNull('artists.image')->orWhere('artists.image', '');
            });
    }

    public function withUserContext(
        User $user,
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
        bool $includePlayCount = false,
        bool $includeAlbumCover = true,
    ): self {
        $this->user = $user;

        return $this
            ->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly))
            ->when($includePlayCount, static fn (self $query) => $query->withPlayCount())
            ->withRatingJoin()
            ->when($includeAlbumCover, static fn (self $query) => $query->withAlbumCover());
    }
}
