<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\ArtistRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayMostPlayedArtist implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly ArtistRepository $artistRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play all songs by the user\'s most played (top) artist. '
            . 'Use this when the user wants to listen to their favorite or most listened-to artist.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return PlaybackService::queueSchema($schema);
    }

    public function handle(Request $request): Stringable|string
    {
        $artists = $this->artistRepository->getMostPlayed(1, $this->context->user);

        if ($artists->isEmpty()) {
            return 'No play history found yet.';
        }

        return $this->playbackService->playArtist($artists->first(), $this->context->user, $request);
    }
}
