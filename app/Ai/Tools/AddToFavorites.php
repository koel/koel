<?php

namespace App\Ai\Tools;

use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\FavoriteService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AddToFavorites implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly SongRepository $songRepository,
        private readonly FavoriteService $favoriteService,
        private readonly ?string $currentSongId,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Add songs to the user\'s favorites (liked songs). '
            . 'Use this when the user wants to like, love, or favorite a song. '
            . 'Can favorite the currently playing song or search for songs by title.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('Search keywords to find songs to favorite. '
                . 'If omitted, the currently playing song will be favorited.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $songs = $this->resolveSongs($request);

        if ($songs->isEmpty()) {
            return (
                'Could not find any songs to favorite. '
                . 'Please specify a song title or make sure a song is currently playing.'
            );
        }

        $this->favoriteService->batchFavorite($songs, $this->user);

        if ($songs->count() === 1) {
            return "Added \"{$songs->first()->title}\" to your favorites.";
        }

        return "Added {$songs->count()} song(s) to your favorites.";
    }

    /** @return Collection<int, Song> */
    private function resolveSongs(Request $request): Collection
    {
        if (isset($request['query'])) {
            return $this->songRepository->search($request['query'], 10, $this->user);
        }

        if ($this->currentSongId) {
            $song = $this->songRepository->findOne($this->currentSongId, $this->user);

            return $song ? collect([$song]) : collect();
        }

        return collect();
    }
}
