<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Models\Song;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySimilarSongs implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play songs similar to a given song. Finds songs by the same artist or in the same genre. '
            . 'Use this when the user wants to hear more songs like the one currently playing or a specific song.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'song_title' => $schema
                ->string()
                ->description(
                    'The title of the song to find similar songs for. If not provided, the currently playing song is used.',
                ),
            ...PlaybackService::limitSchema($schema, 'Maximum number of similar songs to return. Default 50'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $song = $this->resolveSong($request);

        if (!$song) {
            return (
                'Could not determine which song to find similar songs for. '
                . 'Please specify a song title or make sure a song is currently playing.'
            );
        }

        $song->load('genres');

        $songs = $this->songRepository->getSimilar(
            $song,
            PlaybackService::extractLimit($request),
            $this->context->user,
        );

        if ($songs->isEmpty()) {
            return "No similar songs found for \"{$song->title}\".";
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Found';
        $suffix = $queue ? 'to the queue' : 'and queued them for playback';

        return "{$verb} {$songs->count()} song(s) similar to \"{$song->title}\" {$suffix}.";
    }

    private function resolveSong(Request $request): ?Song
    {
        if (isset($request['song_title'])) {
            $songs = $this->songRepository->search($request['song_title'], 1, $this->context->user);

            return $songs->first();
        }

        if ($this->context->currentSongId) {
            return $this->songRepository->findOne($this->context->currentSongId, $this->context->user);
        }

        return null;
    }
}
