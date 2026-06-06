<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use App\Services\Integrations\EncyclopediaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCurrentSong implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly RadioStationRepository $radioStationRepository,
        private readonly EncyclopediaService $encyclopediaService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Get information about what is currently playing (a song or a radio station). '
            . 'Use this when the user asks "what\'s playing", "what song is this", "current song", etc.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        if ($this->context->currentRadioStationId) {
            $station = $this->radioStationRepository->findOne($this->context->currentRadioStationId);

            if ($station) {
                return (
                    "Currently streaming radio station: **{$station->name}**"
                    . ($station->url ? "\nURL: {$station->url}" : '')
                );
            }
        }

        if ($this->context->currentSongId) {
            $song = $this->songRepository->findOne($this->context->currentSongId, $this->context->user);

            if ($song) {
                $parts = ["Currently playing: **{$song->title}**"];

                if ($song->artist_name) {
                    $parts[] = "Artist: {$song->artist_name}";
                }

                if ($song->album_name) {
                    $parts[] = "Album: {$song->album_name}";
                }

                if ($song->year) {
                    $parts[] = "Year: {$song->year}";
                }

                if ($song->genres->isNotEmpty()) {
                    $parts[] = 'Genre: ' . $song->genres->pluck('name')->implode(', ');
                }

                if ($song->artist) {
                    $artistInfo = $this->encyclopediaService->getArtistInformation($song->artist);

                    if ($artistInfo?->bio['summary']) {
                        $parts[] = "\nAbout the artist: " . strip_tags($artistInfo->bio['summary']);
                    }
                }

                if ($song->album) {
                    $albumInfo = $this->encyclopediaService->getAlbumInformation($song->album);

                    if ($albumInfo?->wiki['summary']) {
                        $parts[] = "\nAbout the album: " . strip_tags($albumInfo->wiki['summary']);
                    }
                }

                return implode("\n", $parts);
            }
        }

        return 'Nothing is currently playing.';
    }
}
