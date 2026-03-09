<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRecentlyPlayed implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the user\'s recently played songs. '
            . 'Use this when the user wants to listen to what they played recently or heard last.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Number of recent songs to play. Default 50'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = min((int) ($request['limit'] ?? 50), 500);
        $songs = $this->songRepository->getRecentlyPlayed($limit, $this->user);

        if ($songs->isEmpty()) {
            return 'No recently played songs found.';
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing {$songs->count()} recently played song(s).";
    }
}
