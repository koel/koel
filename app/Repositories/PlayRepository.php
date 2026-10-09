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
    private const int TOP_SONG_LIMIT = 12;

    public function getSummary(User $user, ?Carbon $since, ?Carbon $until = null): ListeningSummary
    {
        $totals = $this
            ->queryForUser($user, $since, $until)
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
        return $this
            ->queryForUser($user, $since)
            ->groupBy('plays.song_id')
            ->select('plays.song_id AS ranked_id')
            ->selectRaw('COUNT(*) AS play_count')
            ->orderByDesc('play_count')
            ->limit(self::TOP_SONG_LIMIT)
            ->toBase()
            ->pluck('play_count', 'ranked_id')
            ->map(static fn (int|string $count): int => (int) $count)
            ->all();
    }

    /** @return array<string, float> listening time in seconds keyed by artist ID, longest first */
    public function getTopArtistIds(User $user, ?Carbon $since): array
    {
        return $this->getIdsByListeningTime($user, $since, 'songs.artist_id');
    }

    /**
     * @param list<string> $artistIds
     *
     * @return array<string, string> the cover of each artist's most listened album, keyed by artist ID
     */
    public function getMostListenedAlbumCovers(User $user, ?Carbon $since, array $artistIds): array
    {
        return $this
            ->queryForUser($user, $since)
            ->join('songs', 'songs.id', '=', 'plays.song_id')
            ->join('albums', 'albums.id', '=', 'songs.album_id')
            ->whereIn('songs.artist_id', $artistIds)
            ->where('albums.cover', '<>', '')
            ->groupBy('songs.artist_id', 'albums.id', 'albums.cover')
            ->select('songs.artist_id', 'albums.cover')
            ->selectRaw('SUM(songs.length) AS listening_time')
            ->orderByDesc('listening_time')
            ->toBase()
            ->get()
            ->unique('artist_id')
            ->pluck('cover', 'artist_id')
            ->all();
    }

    /** @return array<string, float> listening time in seconds keyed by album ID, longest first */
    public function getTopAlbumIds(User $user, ?Carbon $since): array
    {
        return $this->getIdsByListeningTime($user, $since, 'songs.album_id');
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

    /**
     * @return array<string, array{plays: int, listening_time: float}> keyed by the UTC hour the plays fall in,
     *         as ISO 8601 strings
     */
    public function getHourlyListening(User $user, ?Carbon $since): array
    {
        $hour = self::utcHourExpression('plays.played_at');

        return $this
            ->queryForUser($user, $since)
            ->join('songs', 'songs.id', '=', 'plays.song_id')
            ->selectRaw("$hour AS played_hour")
            ->selectRaw('COUNT(*) AS play_count')
            ->selectRaw('COALESCE(SUM(songs.length), 0) AS listening_time')
            ->groupBy('played_hour')
            ->orderBy('played_hour')
            ->toBase()
            ->get()
            ->mapWithKeys(static fn (object $row): array => [
                Carbon::parse($row->played_hour, 'UTC')->toIso8601ZuluString() => [
                    'plays' => (int) $row->play_count,
                    'listening_time' => (float) $row->listening_time,
                ],
            ])
            ->all();
    }

    public function countSongsFirstPlayedSince(User $user, Carbon $since): int
    {
        return $this->countFirstPlayedSince($user, $since, 'songs.id');
    }

    public function countArtistsFirstPlayedSince(User $user, Carbon $since): int
    {
        return $this->countFirstPlayedSince($user, $since, 'songs.artist_id');
    }

    private function countFirstPlayedSince(User $user, Carbon $since, string $column): int
    {
        $firstPlays = Play::query()
            ->join('songs', 'songs.id', '=', 'plays.song_id')
            ->where('plays.user_id', $user->id)
            ->groupBy($column)
            ->select($column)
            ->havingRaw('MIN(plays.played_at) >= ?', [$since->toDateTimeString()]);

        return Play::query()->fromSub($firstPlays, 'first_plays')->count();
    }

    /** @return array<string, float> */
    private function getIdsByListeningTime(User $user, ?Carbon $since, string $column): array
    {
        return $this
            ->queryForUser($user, $since)
            ->join('songs', 'songs.id', '=', 'plays.song_id')
            ->groupBy($column)
            ->select("$column AS ranked_id")
            ->selectRaw('SUM(songs.length) AS listening_time')
            ->orderByDesc('listening_time')
            ->limit(self::TOP_LIMIT)
            ->toBase()
            ->pluck('listening_time', 'ranked_id')
            ->map(static fn (int|float|string $seconds): float => (float) $seconds)
            ->all();
    }

    /** @return Builder<Play> */
    private function queryForUser(User $user, ?Carbon $since, ?Carbon $until = null): Builder
    {
        return Play::query()
            ->where('plays.user_id', $user->id)
            ->when($since, static fn (Builder $query) => $query->where('plays.played_at', '>=', $since))
            ->when($until, static fn (Builder $query) => $query->where('plays.played_at', '<', $until));
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
