<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\AlbumRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayAlbum implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AlbumRepository $albumRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Play all songs from a specific album. Use this when the user wants to listen to an album by name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The album name (or partial name) to search for'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $albums = $this->albumRepository->search($request['name'], 1, $this->context->user);

        if ($albums->isEmpty()) {
            return sprintf('No album matching "%s" found.', $request['name']);
        }

        return $this->playbackService->playAlbum($albums->first(), $this->context->user, $request);
    }
}
