<?php

namespace App\Ai\Tools;

use App\Ai\AiRequestContext;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateSongLyrics implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly SongRepository $songRepository,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Update or add lyrics to a song. '
            . 'Use this after searching the web for lyrics to save them to the song in the library. '
            . 'Can target the currently playing song or find a song by title.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'lyrics' => $schema->string()->description('The lyrics text to save.')->required(),
            'query' => $schema
                ->string()
                ->description('Search keywords to find the song. '
                . 'If omitted, the currently playing song will be used.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $song = null;

        if (isset($request['query'])) {
            $song = $this->songRepository->search($request['query'], 1, $this->context->user)->first();
        } elseif ($this->context->currentSongId) {
            $song = $this->songRepository->findOne($this->context->currentSongId, $this->context->user);
        }

        if (!$song) {
            return 'Could not find the song. Please specify a title or make sure a song is currently playing.';
        }

        $song->lyrics = $request['lyrics'];
        $song->save();

        return "Lyrics for \"{$song->title}\" by {$song->artist->name} have been updated.";
    }
}
