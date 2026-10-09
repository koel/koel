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
use App\Values\Statistics\ListeningStatistics;

class ListeningStatisticsService
{
    public function __construct(
        private readonly PlayRepository $playRepository,
        private readonly SongRepository $songRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
    ) {}

    public function getStatistics(User $user, ListeningPeriod $period): ListeningStatistics
    {
        $since = $period->startsAt();

        $songPlays = $this->playRepository->getTopSongIds($user, $since);
        $artistPlays = $this->playRepository->getTopArtistIds($user, $since);
        $albumPlays = $this->playRepository->getTopAlbumIds($user, $since);

        return ListeningStatistics::make(
            summary: $this->playRepository->getSummary($user, $since),
            topSongs: $this->songRepository
                ->getMany(array_keys($songPlays), preserveOrder: true, scopedUser: $user)
                ->map(static fn (Song $song): array => ['song' => $song, 'plays' => $songPlays[$song->id]])
                ->values()
                ->all(),
            topArtists: $this->artistRepository
                ->getMany(array_keys($artistPlays), preserveOrder: true, user: $user)
                ->map(static fn (Artist $artist): array => ['artist' => $artist, 'plays' => $artistPlays[$artist->id]])
                ->values()
                ->all(),
            topAlbums: $this->albumRepository
                ->getMany(array_keys($albumPlays), preserveOrder: true, user: $user)
                ->map(static fn (Album $album): array => ['album' => $album, 'plays' => $albumPlays[$album->id]])
                ->values()
                ->all(),
            topGenres: $this->playRepository->getTopGenres($user, $since),
            hourlyPlays: $this->playRepository->getHourlyPlayCounts($user, $since),
        );
    }
}
