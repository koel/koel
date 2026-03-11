<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Enums\PlayableType;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRecentlyPlayed implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the user\'s recently played songs. '
            . 'Use this when the user wants to listen to what they played recently or heard last.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            ...PlaybackService::limitSchema($schema, 'Number of recent songs to play. Default 50'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->getRecentlyPlayed(
            PlaybackService::extractLimit($request),
            $this->context->user,
            type: PlayableType::SONG,
        );

        if ($songs->isEmpty()) {
            return 'No recently played songs found.';
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return "{$verb} {$songs->count()} recently played song(s){$suffix}.";
    }
}
