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

class PlayMostPlayed implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the user\'s most played songs. '
            . 'Use this when the user wants to listen to their top tracks, most listened, or most played songs.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            ...PlaybackService::limitSchema($schema, 'Number of top songs to play. Default 50'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->getMostPlayed(
            PlaybackService::extractLimit($request),
            $this->context->user,
            type: PlayableType::SONG,
        );

        if ($songs->isEmpty()) {
            return 'No play history found yet.';
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return "{$verb} your top {$songs->count()} most played song(s){$suffix}.";
    }
}
