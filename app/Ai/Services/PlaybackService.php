<?php

namespace App\Ai\Services;

use App\Ai\AiAssistantResult;
use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Ai\Tools\Request;

class PlaybackService
{
    public function __construct(
        private readonly AiAssistantResult $result,
        private readonly SongRepository $songRepository,
    ) {}

    public static function queueSchema(JsonSchema $schema): array
    {
        return [
            'queue' => $schema
                ->boolean()
                ->description(
                    'If true, add the songs to the end of the current queue instead of playing them immediately. '
                    . 'Default false (play immediately).',
                ),
        ];
    }

    public static function limitSchema(
        JsonSchema $schema,
        string $description = 'Maximum number of songs to return. Default 50',
    ): array {
        return [
            'limit' => $schema->integer()->description($description),
        ];
    }

    public static function extractLimit(Request $request, int $default = 50): int
    {
        return min((int) ($request['limit'] ?? $default), 500);
    }

    /**
     * Set the play_songs result and return whether this is a queue-only operation.
     */
    public function queueSongs(Collection $songs, Request $request): bool
    {
        $queue = (bool) ($request['queue'] ?? false);

        $this->result->action = 'play_songs';
        $this->result->data = ['songs' => $songs, 'queue' => $queue];

        return $queue;
    }

    public function playAlbum(Album $album, User $user, Request $request): string
    {
        $songs = $this->songRepository->getByAlbum($album, $user);

        if ($songs->isEmpty()) {
            return sprintf('The album "%s" has no playable songs.', $album->name);
        }

        $queue = $this->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return sprintf('%s "%s" (%d songs)%s.', $verb, $album->name, $songs->count(), $suffix);
    }

    public function playArtist(Artist $artist, User $user, Request $request): string
    {
        $songs = $this->songRepository->getByArtist($artist, $user);

        if ($songs->isEmpty()) {
            return sprintf('No songs by "%s" found in the library.', $artist->name);
        }

        $queue = $this->queueSongs($songs, $request);
        $verb = $queue ? 'Added' : 'Playing';
        $suffix = $queue ? ' to the queue' : '';

        return "{$verb} {$songs->count()} song(s) by {$artist->name}{$suffix}.";
    }
}
