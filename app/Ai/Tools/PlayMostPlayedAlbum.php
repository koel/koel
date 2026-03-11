<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\AlbumRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayMostPlayedAlbum implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AlbumRepository $albumRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the user\'s most played (top) album. '
            . 'Use this when the user wants to listen to their favorite or most listened-to album.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return PlaybackService::queueSchema($schema);
    }

    public function handle(Request $request): Stringable|string
    {
        $albums = $this->albumRepository->getMostPlayed(1, $this->context->user);

        if ($albums->isEmpty()) {
            return 'No play history found yet.';
        }

        return $this->playbackService->playAlbum($albums->first(), $this->context->user, $request);
    }
}
