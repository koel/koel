<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySongs implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Search for songs in the user\'s music library and queue them for playback. '
            . 'Use this when the user wants to play, listen to, or queue songs. '
            . 'Construct a search query from the user\'s intent (e.g. artist name, song title, album name).'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->required()
                ->description('Search keywords to find songs (e.g. artist name, song title, album name)'),
            ...PlaybackService::limitSchema($schema),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->search(
            $request['query'],
            PlaybackService::extractLimit($request),
            $this->context->user,
        );

        if ($songs->isEmpty()) {
            return 'No songs found matching the criteria.';
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Found';
        $suffix = $queue ? 'to the queue' : 'and queued them for playback';

        return "{$verb} {$songs->count()} song(s) {$suffix}.";
    }
}
