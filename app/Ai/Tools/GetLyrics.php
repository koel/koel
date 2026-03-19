<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Services\SongRequestResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetLyrics implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly SongRequestResolver $songResolver,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Get the lyrics of a song. '
            . 'Use this when the user wants to see or read the lyrics of a song. '
            . 'Can get lyrics for the currently playing song or search by title.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description(
                    'Search keywords to find a specific song by title. '
                    . 'Only provide this when the user explicitly names a different song. '
                    . 'Omit this parameter to use the currently playing song.',
                ),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $song = $this->songResolver->resolveSong($request, $this->context);

        if (!$song) {
            return 'Could not find the song. Please specify a title or make sure a song is currently playing.';
        }

        if (!$song->lyrics) {
            return sprintf(
                'No lyrics available for "%s" by %s. Ask the user if they would like you to search the web for the lyrics.',
                $song->title,
                $song->artist->name,
            );
        }

        $this->result->action = 'show_lyrics';
        $this->result->data = ['lyrics' => $song->lyrics];

        return sprintf('Here are the lyrics for "%s" by %s.', $song->title, $song->artist->name);
    }
}
