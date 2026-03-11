<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\ArtistRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayArtist implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly ArtistRepository $artistRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Play all songs by a specific artist. Use this when the user wants to listen to a particular artist.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The artist name (or partial name) to search for'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $artists = $this->artistRepository->search($request['name'], 1, $this->context->user);

        if ($artists->isEmpty()) {
            return sprintf('No artist matching "%s" found.', $request['name']);
        }

        return $this->playbackService->playArtist($artists->first(), $this->context->user, $request);
    }
}
