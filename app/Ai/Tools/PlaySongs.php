<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySongs implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Search for songs in the user\'s music library and queue them for playback. '
            . 'Use this when the user wants to play, listen to, or queue songs. '
            . 'Construct a search query from the user\'s intent (e.g. artist name, song title, album name).'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->required()
                ->description('Search keywords to find songs (e.g. artist name, song title, album name)'),
            'limit' => $schema->integer()->description('Maximum number of songs to return. Default 50'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = min((int) ($request['limit'] ?? 50), 500);
        $songs = $this->songRepository->search($request['query'], $limit, $this->user);

        $this->result->action = 'play_songs';
        $this->result->data = [
            'songs' => $songs,
        ];

        $count = $songs->count();

        if ($count === 0) {
            return 'No songs found matching the criteria.';
        }

        return "Found {$count} song(s) and queued them for playback.";
    }
}
