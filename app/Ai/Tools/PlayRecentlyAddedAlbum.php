<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\AlbumRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRecentlyAddedAlbum implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AlbumRepository $albumRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the most recently added album in the user\'s library. '
            . 'Use this when the user wants to listen to the latest or newest album added.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return PlaybackService::queueSchema($schema);
    }

    public function handle(Request $request): Stringable|string
    {
        $albums = $this->albumRepository->getRecentlyAdded(1, $this->context->user);

        if ($albums->isEmpty()) {
            return 'No albums found in the library.';
        }

        return $this->playbackService->playAlbum($albums->first(), $this->context->user, $request);
    }
}
