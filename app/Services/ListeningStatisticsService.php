<?php

namespace App\Services;

use App\Enums\ListeningPeriod;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PlayRepository;
use App\Repositories\SongRepository;
use App\Values\Statistics\Discoveries;
use App\Values\Statistics\ListeningStatistics;
use App\Values\Statistics\ListeningSummary;
use Illuminate\Support\Facades\Cache;

/**
 * @phpstan-type PlayTotals array{
 *     song_plays: array<string, int>,
 *     artist_listening_times: array<string, float>,
 *     album_covers: array<string, string>,
 *     album_listening_times: array<string, float>,
 *     summary: ListeningSummary,
 *     top_genres: list<array{id: string, name: string, listening_time: float}>,
 *     hourly_listening: array<string, array{plays: int, listening_time: float}>,
 *     previous_summary: ?ListeningSummary,
 *     discoveries: ?Discoveries,
 *     played_hours: list<string>,
 * }
 */
class ListeningStatisticsService
{
    private const string PLAYS_CHANGED_AT_CACHE_KEY = 'plays changed at';

    public function __construct(
        private readonly PlayRepository $playRepository,
        private readonly SongRepository $songRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ListeningStreakCalculator $streakCalculator,
        private readonly LibrarySummaryService $librarySummaryService,
    ) {}

    public function getStatistics(User $user, ListeningPeriod $period, string $timezone): ListeningStatistics
    {
        $totals = $this->rememberPlayTotals($user, $period);

        $songPlays = $totals['song_plays'];
        $artistListeningTimes = $totals['artist_listening_times'];
        $albumCovers = $totals['album_covers'];
        $albumListeningTimes = $totals['album_listening_times'];

        return ListeningStatistics::make(
            summary: $totals['summary'],
            topSongs: $this->songRepository
                ->getMany(array_keys($songPlays), preserveOrder: true, scopedUser: $user)
                ->map(static fn (Song $song): array => ['song' => $song, 'plays' => $songPlays[$song->id]])
                ->values()
                ->all(),
            topArtists: $this->artistRepository
                ->getMany(array_keys($artistListeningTimes), preserveOrder: true, user: $user)
                ->map(static fn (Artist $artist): array => [
                    'artist' => $artist,
                    'listening_time' => $artistListeningTimes[$artist->id],
                    'album_cover' => image_storage_url($albumCovers[$artist->id] ?? null),
                ])
                ->values()
                ->all(),
            topAlbums: $this->albumRepository
                ->getMany(array_keys($albumListeningTimes), preserveOrder: true, user: $user)
                ->map(static fn (Album $album): array => [
                    'album' => $album,
                    'listening_time' => $albumListeningTimes[$album->id],
                ])
                ->values()
                ->all(),
            topGenres: $totals['top_genres'],
            hourlyListening: $totals['hourly_listening'],
            previousSummary: $totals['previous_summary'],
            discoveries: $totals['discoveries'],
            streak: $this->streakCalculator->calculateStreak($totals['played_hours'], $timezone),
        );
    }

    public function markPlaysChanged(User $user): void
    {
        Cache::forever(cache_key(self::PLAYS_CHANGED_AT_CACHE_KEY, $user->id), now()->getPreciseTimestamp());
    }

    /** @return PlayTotals */
    private function rememberPlayTotals(User $user, ListeningPeriod $period): array
    {
        $changedAt = [
            'plays' => Cache::get(cache_key(self::PLAYS_CHANGED_AT_CACHE_KEY, $user->id)),
            'library' => $this->librarySummaryService->getLibraryChangedAt(),
        ];

        $key = cache_key('listening statistics', $user->id, $period->value);
        $cached = Cache::get($key);

        if (is_array($cached) && $cached['changed_at'] === $changedAt) {
            return $cached['totals'];
        }

        return tap($this->computePlayTotals($user, $period), static fn (array $totals) => Cache::put(
            $key,
            ['changed_at' => $changedAt, 'totals' => $totals],
            $period === ListeningPeriod::ALL_TIME ? now()->addWeek() : now()->addHour(),
        ));
    }

    /** @return PlayTotals */
    private function computePlayTotals(User $user, ListeningPeriod $period): array
    {
        $since = $period->startsAt();
        $previousSince = $period->previousStartsAt();

        $artistListeningTimes = $this->playRepository->getTopArtistIds($user, $since);
        $hourlyListening = $this->playRepository->getHourlyListening($user, $since);

        return [
            'song_plays' => $this->playRepository->getTopSongIds($user, $since),
            'artist_listening_times' => $artistListeningTimes,
            'album_covers' => $this->playRepository->getMostListenedAlbumCovers(
                $user,
                $since,
                array_map(strval(...), array_keys($artistListeningTimes)),
            ),
            'album_listening_times' => $this->playRepository->getTopAlbumIds($user, $since),
            'summary' => $this->playRepository->getSummary($user, $since),
            'top_genres' => $this->playRepository->getTopGenres($user, $since),
            'hourly_listening' => $hourlyListening,
            'previous_summary' => $since && $previousSince
                ? $this->playRepository->getSummary($user, $previousSince, $since)
                : null,
            'discoveries' => $since
                ? Discoveries::make(
                    songCount: $this->playRepository->countSongsFirstPlayedSince($user, $since),
                    artistCount: $this->playRepository->countArtistsFirstPlayedSince($user, $since),
                )
                : null,
            'played_hours' => $since ? $this->playRepository->getPlayedHours($user) : array_keys($hourlyListening),
        ];
    }
}
