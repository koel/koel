<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayMostPlayed implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the user\'s most played songs. '
            . 'Use this when the user wants to listen to their top tracks, most listened, or most played songs.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Number of top songs to play. Default 50'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = min((int) ($request['limit'] ?? 50), 500);
        $songs = $this->songRepository->getMostPlayed($limit, $this->user);

        if ($songs->isEmpty()) {
            return 'No play history found yet.';
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing your top {$songs->count()} most played song(s).";
    }
}
