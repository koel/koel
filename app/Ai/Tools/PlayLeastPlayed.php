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

class PlayLeastPlayed implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play songs the user has rarely or never listened to. '
            . 'Use this when the user wants to rediscover songs, listen to something they haven\'t heard, '
            . 'or play their least played tracks.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            ...PlaybackService::limitSchema($schema, 'Number of songs to play. Default 50'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->getLeastPlayed(
            PlaybackService::extractLimit($request),
            $this->context->user,
            type: PlayableType::SONG,
        );

        if ($songs->isEmpty()) {
            return 'No songs found in the library.';
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return "{$verb} {$songs->count()} rarely or never played song(s){$suffix}.";
    }
}
