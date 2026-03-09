<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRecentlyAddedArtist implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play all songs by the most recently added artist in the user\'s library. '
            . 'Use this when the user wants to listen to the latest or newest artist added.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $recentSong = $this->songRepository->getRecentlyAdded(1, $this->user)->first();

        if (!$recentSong) {
            return 'No songs found in the library.';
        }

        $artist = $recentSong->artist;
        $songs = $this->songRepository->getByArtist($artist, $this->user);

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing {$songs->count()} song(s) by {$artist->name} (most recently added artist).";
    }
}
