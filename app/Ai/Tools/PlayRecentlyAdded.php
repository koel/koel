<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRecentlyAdded implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play songs recently added to the user\'s library. '
            . 'Use this when the user wants to listen to new additions, recently added, or newly imported songs.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Number of recently added songs to play. Default 50'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = min((int) ($request['limit'] ?? 50), 500);
        $songs = $this->songRepository->getRecentlyAdded($limit, $this->user);

        if ($songs->isEmpty()) {
            return 'No songs found in the library.';
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing {$songs->count()} recently added song(s).";
    }
}
