<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayPlaylist implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly PlaylistRepository $playlistRepository,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play all songs from a specific playlist. '
            . 'Use this when the user wants to listen to or play a playlist by name.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The playlist name (or partial name) to search for'),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $playlist = $this->playlistRepository->findAccessibleByName($request['name'], $this->context->user);

        if (!$playlist) {
            return "No playlist matching \"{$request['name']}\" found.";
        }

        $songs = $this->songRepository->getByPlaylist($playlist, $this->context->user);

        if ($songs->isEmpty()) {
            return "The playlist \"{$playlist->name}\" has no songs.";
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return "{$verb} \"{$playlist->name}\" ({$songs->count()} songs){$suffix}.";
    }
}
