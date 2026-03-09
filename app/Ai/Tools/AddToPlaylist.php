<?php

namespace App\Ai\Tools;

use App\Models\Song;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use App\Services\PlaylistService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AddToPlaylist implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly SongRepository $songRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistService $playlistService,
        private readonly ?string $currentSongId,
    ) {}

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
        $playlist = $this->playlistRepository->findAccessibleByName($request['playlist_name'], $this->user);

        if (!$playlist) {
            return "No playlist matching \"{$request['playlist_name']}\" found.";
        }

        if ($playlist->is_smart) {
            return "Cannot add songs to \"{$playlist->name}\" because it's a smart playlist with automatic rules.";
        }

        $songs = $this->resolveSongs($request);

        if ($songs->isEmpty()) {
            return (
                'Could not find any songs to add. '
                . 'Please specify a song title or make sure a song is currently playing.'
            );
        }

        $this->playlistService->addPlayablesToPlaylist($playlist, $songs, $this->user);

        if ($songs->count() === 1) {
            return "Added \"{$songs->first()->title}\" to \"{$playlist->name}\".";
        }

        return "Added {$songs->count()} song(s) to \"{$playlist->name}\".";
    }

    /** @return Collection<int, Song> */
    private function resolveSongs(Request $request): Collection
    {
        if (isset($request['song_query'])) {
            return $this->songRepository->search($request['song_query'], 10, $this->user);
        }

        if ($this->currentSongId) {
            $song = $this->songRepository->findOne($this->currentSongId, $this->user);

            return $song ? collect([$song]) : collect();
        }

        return collect();
    }
}
