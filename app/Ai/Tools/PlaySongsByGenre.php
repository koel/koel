<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Ai\Services\PlaybackService;
use App\Repositories\GenreRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySongsByGenre implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly GenreRepository $genreRepository,
        private readonly SongRepository $songRepository,
        private readonly PlaybackService $playbackService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Play songs from a specific genre. Use this when the user wants to listen to a genre like rock, jazz, pop, etc.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'genre' => $schema->string()->required()->description('The genre name (e.g. rock, jazz, pop, classical)'),
            ...PlaybackService::limitSchema($schema),
            ...PlaybackService::queueSchema($schema),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $genre = $this->genreRepository->searchByName($request['genre']);

        if (!$genre) {
            return sprintf('No genre matching "%s" found in the library.', $request['genre']);
        }

        $songs = $this->songRepository->getByGenre(
            $genre,
            PlaybackService::extractLimit($request),
            random: true,
            scopedUser: $this->context->user,
        );

        if ($songs->isEmpty()) {
            return sprintf('No songs found in the "%s" genre.', $genre->name);
        }

        $queue = $this->playbackService->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Found';
        $suffix = $queue ? 'to the queue' : 'and queued them for playback';

        return "{$verb} {$songs->count()} {$genre->name} song(s) {$suffix}.";
    }
}
