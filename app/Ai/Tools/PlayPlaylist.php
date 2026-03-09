<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayPlaylist implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly PlaylistRepository $playlistRepository,
        private readonly SongRepository $songRepository,
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
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $playlist = $this->playlistRepository->findAccessibleByName($request['name'], $this->user);

        if (!$playlist) {
            return "No playlist matching \"{$request['name']}\" found.";
        }

        $songs = $this->songRepository->getByPlaylist($playlist, $this->user);

        if ($songs->isEmpty()) {
            return "The playlist \"{$playlist->name}\" has no songs.";
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing \"{$playlist->name}\" ({$songs->count()} songs).";
    }
}
