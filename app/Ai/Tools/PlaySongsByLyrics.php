<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySongsByLyrics implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Search for songs by their lyrics and queue them for playback. '
            . 'Use this when the user remembers some lyrics but not the song title or artist. '
            . 'The lyrics do not need to be exact — partial or approximate phrases work.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'lyrics' => $schema
                ->string()
                ->required()
                ->description('The lyrics or phrase the user remembers. Can be partial or approximate.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->searchByLyrics($request['lyrics'], 50, $this->user);

        if ($songs->isEmpty()) {
            return 'No songs found with matching lyrics in the library.';
        }

        $this->result->action = 'play_songs';
        $this->result->data = [
            'songs' => $songs,
        ];

        $count = $songs->count();
        $titles = $songs->take(5)->pluck('title')->implode(', ');

        return "Found {$count} song(s) with matching lyrics: {$titles}. Queued for playback.";
    }
}
