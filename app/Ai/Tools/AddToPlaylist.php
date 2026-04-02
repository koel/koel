<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Services\SongRequestResolver;
use App\Repositories\PlaylistRepository;
use App\Services\PlaylistService;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AddToPlaylist implements Tool
{
    public function __construct(
        private readonly AiRequestContext $context,
        private readonly AiAssistantResult $result,
        private readonly SongRequestResolver $songResolver,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistService $playlistService,
        private Gate $gate,
    ) {
        $this->gate = $this->gate->forUser($this->context->user);
    }

    public function description(): Stringable|string
    {
        return (
            'Add songs to an existing playlist. '
            . 'Use this when the user wants to add a song or songs to a specific playlist by name. '
            . 'Can add the currently playing song or search for songs by title.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'playlist_name' => $schema
                ->string()
                ->required()
                ->description('The name (or partial name) of the playlist to add songs to'),
            'song_query' => $schema
                ->string()
                ->description('Search keywords to find songs to add. '
                . 'If omitted, the currently playing song will be added.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $playlist = $this->playlistRepository->searchAccessibleByName($request['playlist_name'], $this->context->user);

        if (!$playlist) {
            return sprintf('No playlist matching "%s" found.', $request['playlist_name']);
        }

        if ($this->gate->denies('collaborate', $playlist)) {
            return sprintf('You don\'t have permission to add songs to "%s".', $playlist->name);
        }

        if ($playlist->is_smart) {
            return sprintf(
                'Cannot add songs to "%s" because it\'s a smart playlist with automatic rules.',
                $playlist->name,
            );
        }

        $songs = $this->songResolver->resolveSongs($request, $this->context);

        if ($songs->isEmpty()) {
            return (
                'Could not find any songs to add. '
                . 'Please specify a song title or make sure a song is currently playing.'
            );
        }

        $this->playlistService->addPlayablesToPlaylist($playlist, $songs, $this->context->user);

        $this->result->action = 'add_to_playlist';
        $this->result->data = ['songs' => $songs, 'playlist' => $playlist];

        if ($songs->count() === 1) {
            return sprintf('Added "%s" to "%s".', $songs->first()->title, $playlist->name);
        }

        return sprintf('Added %d song(s) to "%s".', $songs->count(), $playlist->name);
    }
}
