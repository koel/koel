<?php

namespace App\Repositories;

use App\Models\Play;
use App\Models\User;
use App\Values\Statistics\ListeningSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** @extends Repository<Play> */
class PlayRepository extends Repository
{
    private const int TOP_LIMIT = 10;

    public function getSummary(User $user, ?Carbon $since): ListeningSummary
    {
        $totals = $this
            ->queryForUser($user, $since)
            ->join('songs', 'songs.id', '=', 'plays.song_id')
            ->selectRaw('COUNT(*) AS play_count')
            ->selectRaw('COALESCE(SUM(songs.length), 0) AS listening_time')
            ->selectRaw('COUNT(DISTINCT plays.song_id) AS song_count')
            ->selectRaw('COUNT(DISTINCT songs.artist_id) AS artist_count')
            ->toBase()
            ->first();

        return ListeningSummary::make(
            plays: (int) $totals->play_count,
            listeningTime: (float) $totals->listening_time,
            songCount: (int) $totals->song_count,
            artistCount: (int) $totals->artist_count,
        );
    }

    /** @return array<string, int> play counts keyed by song ID, most played first */
    public function getTopSongIds(User $user, ?Carbon $since): array
    {
        return $this->getTopIds($user, $since, 'plays.song_id');
    }

    /** @return array<string, int> play counts keyed by artist ID, most played first */
    public function getTopArtistIds(User $user, ?Carbon $since): array
    {
        return $this->getTopIds($user, $since, 'songs.artist_id');
    }

    /** @return array<string, int> play counts keyed by album ID, most played first */
    public function getTopAlbumIds(User $user, ?Carbon $since): array
    {
        return $this->getTopIds($user, $since, 'songs.album_id');
    }

    /** @return list<array{id: string, name: string, plays: int}> most played first */
    public function getTopGenres(User $user, ?Carbon $since): array
    {
        return $this
            ->queryForUser($user, $since)
            ->join('genre_song', 'genre_song.song_id', '=', 'plays.song_id')
            ->join('genres', 'genres.id', '=', 'genre_song.genre_id')
            ->groupBy('genres.public_id', 'genres.name')
            ->select('genres.public_id', 'genres.name')
            ->selectRaw('COUNT(*) AS play_count')
            ->orderByDesc('play_count')
            ->limit(self::TOP_LIMIT)
            ->toBase()
            ->get()
            ->map(static fn (object $genre): array => [
                'id' => $genre->public_id,
                'name' => $genre->name,
                'plays' => (int) $genre->play_count,
            ])
            ->all();
    }

    /** @return array<string, int> play counts keyed by the UTC hour they fall in, as ISO 8601 strings */
    public function getHourlyPlayCounts(User $user, ?Carbon $since): array
    {
        $hour = self::utcHourExpression('plays.played_at');

        return $this
            ->queryForUser($user, $since)
            ->selectRaw("$hour AS played_hour")
            ->selectRaw('COUNT(*) AS play_count')
            ->groupBy('played_hour')
            ->orderBy('played_hour')
            ->toBase()
            ->pluck('play_count', 'played_hour')
            ->mapWithKeys(static fn (int|string $count, string $playedHour): array => [
                Carbon::parse($playedHour, 'UTC')->toIso8601ZuluString() => (int) $count,
            ])
            ->all();
    }

    /** @return array<string, int> */
    private function getTopIds(User $user, ?Carbon $since, string $column): array
    {
        return $this
            ->queryForUser($user, $since)
            ->join('songs', 'songs.id', '=', 'plays.song_id')
            ->groupBy($column)
            ->select("$column AS ranked_id")
            ->selectRaw('COUNT(*) AS play_count')
            ->orderByDesc('play_count')
            ->limit(self::TOP_LIMIT)
            ->toBase()
            ->pluck('play_count', 'ranked_id')
            ->map(static fn (int|string $count): int => (int) $count)
            ->all();
    }

    /** @return Builder<Play> */
    private function queryForUser(User $user, ?Carbon $since): Builder
    {
        return Play::query()
            ->where('plays.user_id', $user->id)
            ->when($since, static fn (Builder $query) => $query->where('plays.played_at', '>=', $since));
    }

    private static function utcHourExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "to_char(date_trunc('hour', $column), 'YYYY-MM-DD HH24:00:00')",
            'sqlite' => "strftime('%Y-%m-%d %H:00:00', $column)",
            default => "DATE_FORMAT($column, '%Y-%m-%d %H:00:00')",
        };
    }
}
