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

class ListeningStatisticsService
{
    public function __construct(
        private readonly PlayRepository $playRepository,
        private readonly SongRepository $songRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ListeningStreakCalculator $streakCalculator,
    ) {}

    public function getStatistics(User $user, ListeningPeriod $period, string $timezone): ListeningStatistics
    {
        $since = $period->startsAt();
        $previousSince = $period->previousStartsAt();

        $songPlays = $this->playRepository->getTopSongIds($user, $since);
        $artistListeningTimes = $this->playRepository->getTopArtistIds($user, $since);
        $albumListeningTimes = $this->playRepository->getTopAlbumIds($user, $since);

        return ListeningStatistics::make(
            summary: $this->playRepository->getSummary($user, $since),
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
            topGenres: $this->playRepository->getTopGenres($user, $since),
            hourlyListening: $this->playRepository->getHourlyListening($user, $since),
            previousSummary: $since && $previousSince
                ? $this->playRepository->getSummary($user, $previousSince, $since)
                : null,
            discoveries: $since
                ? Discoveries::make(
                    songCount: $this->playRepository->countSongsFirstPlayedSince($user, $since),
                    artistCount: $this->playRepository->countArtistsFirstPlayedSince($user, $since),
                )
                : null,
            streak: $this->streakCalculator->calculateStreak(
                array_keys($this->playRepository->getHourlyListening($user, null)),
                $timezone,
            ),
        );
    }
}
