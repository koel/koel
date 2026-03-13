<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Ai\Services\SongRequestResolver;
use App\Models\Song;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySimilarSongs implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
        private readonly SongRequestResolver $songResolver,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Find and optionally play songs similar to a given song. '
            . 'Finds songs by the same artist or in the same genre. '
            . 'Set preview=true to list the songs without playing them (for discovery or recommendation requests). '
            . 'Set preview=false to play or queue them immediately.'
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
            'preview' => $schema
                ->boolean()
                ->description(
                    'If true, list the songs without playing them. '
                    . 'Use this when the user asks to discover or see similar songs. Default false.',
                ),
            ...PlaybackService::limitSchema(
                $schema,
                'Maximum number of similar songs to return. Default 20 for preview, 50 for playback',
            ),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $song = $this->songResolver->resolveSong($request, $this->context, 'song_title');

        if (!$song) {
            return (
                'Could not determine which song to find similar songs for. '
                . 'Please specify a song title or make sure a song is currently playing.'
            );
        }

        $song->load('genres');

        $preview = (bool) ($request['preview'] ?? false);
        $defaultLimit = $preview ? 20 : 50;
        $limit = min((int) ($request['limit'] ?? $defaultLimit), 500);

        $songs = $this->songRepository->getSimilar($song, $limit, $this->context->user);

        if ($songs->isEmpty()) {
            return sprintf('No similar songs found for "%s".', $song->title);
        }

        if ($preview) {
            /** @var Collection<int, Song> $songs */
            $list = $songs->map(
                static fn (Song $s, int $i) => ($i + 1) . '. **' . $s->title . '** — ' . $s->artist->name,
            )->implode("\n");

            $this->result->action = 'suggest_songs';
            $this->result->data = ['songs' => $songs, 'list' => $list];

            return sprintf(
                'Found %d song(s) similar to "%s". Ask the user if they want to play or queue them.',
                $songs->count(),
                $song->title,
            );
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return sprintf('%s %d song(s) similar to "%s"%s.', $verb, $songs->count(), $song->title, $suffix);
    }
}
