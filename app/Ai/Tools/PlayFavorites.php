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

class PlayFavorites implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the user\'s favorite (liked) songs. '
            . 'Use this when the user wants to listen to their favorites, liked songs, or loved songs.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return PlaybackService::queueSchema($schema);
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->getFavorites($this->context->user, type: PlayableType::SONG);

        if ($songs->isEmpty()) {
            return 'You have no favorite songs yet.';
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return "{$verb} {$songs->count()} favorite song(s){$suffix}.";
    }
}
