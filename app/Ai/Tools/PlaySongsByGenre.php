<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\Genre;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySongsByGenre implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return 'Play songs from a specific genre. Use this when the user wants to listen to a genre like rock, jazz, pop, etc.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'genre' => $schema->string()->required()->description('The genre name (e.g. rock, jazz, pop, classical)'),
            'limit' => $schema->integer()->description('Maximum number of songs to return. Default 50'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $genre = Genre::query()->where('name', 'like', '%' . $request['genre'] . '%')->first();

        if (!$genre) {
            return "No genre matching \"{$request['genre']}\" found in the library.";
        }

        $limit = min((int) ($request['limit'] ?? 50), 500);
        $songs = $this->songRepository->getByGenre($genre, $limit, random: true, scopedUser: $this->user);

        $this->result->action = 'play_songs';
        $this->result->data = [
            'songs' => $songs,
        ];

        $count = $songs->count();

        if ($count === 0) {
            return "No songs found in the \"{$genre->name}\" genre.";
        }

        return "Found {$count} {$genre->name} song(s) and queued them for playback.";
    }
}
