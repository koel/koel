<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayRecentlyAddedAlbum implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play the most recently added album in the user\'s library. '
            . 'Use this when the user wants to listen to the latest or newest album added.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $albums = $this->albumRepository->getRecentlyAdded(1, $this->user);

        if ($albums->isEmpty()) {
            return 'No albums found in the library.';
        }

        $album = $albums->first();
        $songs = $this->songRepository->getByAlbum($album, $this->user);

        if ($songs->isEmpty()) {
            return "The album \"{$album->name}\" has no playable songs.";
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing \"{$album->name}\" by {$album->artist->name} ({$songs->count()} songs).";
    }
}
