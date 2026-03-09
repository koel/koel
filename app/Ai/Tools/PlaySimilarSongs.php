<?php

namespace App\Ai\Tools;

use App\Ai\AiAssistantResult;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class PlaySimilarSongs implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
        private readonly ?string $currentSongId,
    ) {}

    public function description(): Stringable|string
    {
        return (
            'Play songs similar to a given song. Finds songs by the same artist or in the same genre. '
            . 'Use this when the user wants to hear more songs like the one currently playing or a specific song.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'song_title' => $schema
                ->string()
                ->description(
                    'The title of the song to find similar songs for. If not provided, the currently playing song is used.',
                ),
            'limit' => $schema->integer()->description('Maximum number of similar songs to return. Default 50'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $song = $this->resolveSong($request);

        if (!$song) {
            return (
                'Could not determine which song to find similar songs for. '
                . 'Please specify a song title or make sure a song is currently playing.'
            );
        }

        $song->load('genres');

        $limit = min((int) ($request['limit'] ?? 50), 500);
        $songs = $this->songRepository->getSimilar($song, $limit, $this->user);

        if ($songs->isEmpty()) {
            return "No similar songs found for \"{$song->title}\".";
        }

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs];

        $count = $songs->count();

        return "Found {$count} song(s) similar to \"{$song->title}\" and queued them for playback.";
    }

    private function resolveSong(Request $request): ?Song
    {
        if (isset($request['song_title'])) {
            $songs = $this->songRepository->search($request['song_title'], 1, $this->user);

            return $songs->first();
        }

        if ($this->currentSongId) {
            return $this->songRepository->findOne($this->currentSongId, $this->user);
        }

        return null;
    }
}
