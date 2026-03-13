<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Services\SongRequestResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateSongLyrics implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly SongRequestResolver $songResolver,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Update or add lyrics to a song. '
            . 'Use this after searching the web for lyrics to save them to the song in the library. '
            . 'Can target the currently playing song or find a song by title. '
            . 'When searching for lyrics, prefer synchronized/LRC format (with timestamps like [00:12.34]) '
            . 'over plain text lyrics, as Koel supports synced lyrics playback.'
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
        $song = $this->songResolver->resolveSong($request, $this->context);

        if (!$song) {
            return 'Could not find the song. Please specify a title or make sure a song is currently playing.';
        }

        $song->lyrics = $request['lyrics'];
        $song->save();

        $this->result->action = 'update_lyrics';
        $this->result->data = ['lyrics' => $song->lyrics, 'song' => $song];

        return sprintf('Here are the lyrics for "%s" by %s.', $song->title, $song->artist->name);
    }
}
