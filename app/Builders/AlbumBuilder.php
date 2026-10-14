<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use LogicException;
use Webmozart\Assert\Assert;

/**
 * @extends FavoriteableBuilder<Album>
 */
class AlbumBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const array SORT_COLUMNS_NORMALIZE_MAP = [
        'name' => 'albums.name',
        'year' => 'albums.year',
        'created_at' => 'albums.created_at',
        'artist_name' => 'albums.artist_name',
        'length' => 'length',
        'rating' => 'rating',
    ];

    private const array VALID_SORT_COLUMNS = [
        'albums.name',
        'albums.year',
        'albums.created_at',
        'albums.artist_name',
        'favorite', // alias column for favorite status
        'length', // alias column for total duration
        'rating', // alias column for the requesting user's rating
    ];

    public function onlyStandard(): self
    {
        return $this->whereNot('albums.name', Album::UNKNOWN_NAME);
    }

    public function onlyIdentifiable(): self
    {
        return $this->onlyStandard()->whereNot('albums.artist_name', Artist::UNKNOWN_NAME);
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

        throw_unless($this->user, new LogicException('User must be set to query accessible albums.'));

        self::limitToAlbumsAccessibleByUser($this, $this->user);

        return $this;
    }

    /**
     * Limit a query on the `albums` table to the albums the user can open: their own, plus albums with a public
     * song shared by someone else in their organization when they include public media.
     */
    public static function limitToAlbumsAccessibleByUser(Builder|QueryBuilder $query, User $user): void
    {
        if (License::isCommunity()) {
            return;
        }

        if (!$user->preferences->includePublicMedia) {
            $query->where('albums.user_id', $user->id);

            return;
        }

        $query->where(static function (Builder|QueryBuilder $ownedOrShared) use ($user): void {
            $ownedOrShared
                ->where('albums.user_id', $user->id)
                ->orWhereExists(static function (QueryBuilder $sub) use ($user): void {
                    $sub
                        ->select(DB::raw(1))
                        ->from('songs')
                        ->join('users', 'songs.owner_id', 'users.id')
                        ->whereColumn('songs.album_id', 'albums.id')
                        ->where('songs.is_public', true)
                        ->where('users.organization_id', $user->organization_id)
                        ->where('songs.owner_id', '<>', $user->id);
                });
        });
    }

    public function withPlayCount(bool $playedOnly = false): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        $playCountsByAlbum = DB::table('interactions')
            ->join('songs as songs_for_playcount', 'songs_for_playcount.id', 'interactions.song_id')
            ->where('interactions.user_id', $this->user->id)
            ->where('interactions.play_count', '>', 0)
            ->groupBy('songs_for_playcount.album_id')
            ->select('songs_for_playcount.album_id')
            ->selectRaw('SUM(interactions.play_count) as play_count');

        return $this->joinSub(
            $playCountsByAlbum,
            'album_play_counts',
            'album_play_counts.album_id',
            '=',
            'albums.id',
            $playedOnly ? 'inner' : 'left',
        )->addSelect(DB::raw('COALESCE(album_play_counts.play_count, 0) as play_count'));
    }

    private function withLengthSubquery(): self
    {
        return $this->addSelect([
            'length' => DB::table('songs')
                ->whereColumn('songs.album_id', 'albums.id')
                ->selectRaw('COALESCE(SUM(length), 0)'),
        ]);
    }

    private function withLastPlayedSubquery(): self
    {
        throw_unless($this->user, new LogicException('User must be set to query last played timestamps.'));

        return $this->addSelect([
            'last_played_at' => DB::table('interactions')
                ->join('songs as songs_for_last_played', 'songs_for_last_played.id', 'interactions.song_id')
                ->whereColumn('songs_for_last_played.album_id', 'albums.id')
                ->where('interactions.user_id', $this->user->id)
                ->selectRaw('MAX(interactions.last_played_at)'),
        ]);
    }

    public function withUserContext(
        User $user,
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
        bool $includePlayCount = false,
    ): self {
        $this->user = $user;

        return $this
            ->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly))
            ->when($includePlayCount, static fn (self $query) => $query->withPlayCount())
            ->withLengthSubquery()
            ->withLastPlayedSubquery()
            ->withRatingJoin();
    }

    public function sort(string $column, string $direction): self
    {
        $column = self::SORT_COLUMNS_NORMALIZE_MAP[$column] ?? $column;

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        return $this
            ->orderBy($column, $direction)
            ->when($column === 'albums.artist_name', static fn (self $query) => $query->orderBy('albums.name'))
            ->when($column === 'albums.year', static fn (self $query) => $query->orderBy('albums.name'))
            ->when($column === 'favorite', static fn (self $query) => $query->orderBy('albums.name'));
    }
}
