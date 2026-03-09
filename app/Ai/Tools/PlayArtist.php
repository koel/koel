<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlayArtist implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly ArtistRepository $artistRepository,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play all songs by a specific artist. ' . 'Use this when the user wants to listen to a particular artist.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The artist name (or partial name) to search for'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $artists = $this->artistRepository->search($request['name'], 1, $this->user);

        if ($artists->isEmpty()) {
            return "No artist matching \"{$request['name']}\" found.";
        }

        $artist = $artists->first();
        $songs = $this->songRepository->getByArtist($artist, $this->user);

        if ($songs->isEmpty()) {
            return "No songs by \"{$artist->name}\" found.";
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        return "Playing {$songs->count()} song(s) by {$artist->name}.";
    }
}
