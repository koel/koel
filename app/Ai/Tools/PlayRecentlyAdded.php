<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRecentlyAdded implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play songs recently added to the user\'s library. '
            . 'Use this when the user wants to listen to new additions, recently added, or newly imported songs.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            ...PlaybackService::limitSchema($schema, 'Number of recently added songs to play. Default 50'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->getRecentlyAdded(PlaybackService::extractLimit($request), $this->context->user);

        if ($songs->isEmpty()) {
            return 'No songs found in the library.';
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return "{$verb} {$songs->count()} recently added song(s){$suffix}.";
    }
}
