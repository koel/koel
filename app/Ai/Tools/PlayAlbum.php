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

class PlayAlbum implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return 'Play all songs from a specific album. ' . 'Use this when the user wants to listen to an album by name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The album name (or partial name) to search for'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $albums = $this->albumRepository->search($request['name'], 1, $this->user);

        if ($albums->isEmpty()) {
            return "No album matching \"{$request['name']}\" found.";
        }

        $album = $albums->first();
        $songs = $this->songRepository->getByAlbum($album, $this->user);

        if ($songs->isEmpty()) {
            return "The album \"{$album->name}\" has no playable songs.";
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing \"{$album->name}\" ({$songs->count()} songs).";
    }
}
