<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayFavorites implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the user\'s favorite (liked) songs. '
            . 'Use this when the user wants to listen to their favorites, liked songs, or loved songs.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->songRepository->getFavorites($this->user);

        if ($songs->isEmpty()) {
            return 'You have no favorite songs yet.';
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing {$songs->count()} favorite song(s).";
    }
}
