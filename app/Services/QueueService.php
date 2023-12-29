<?php

namespace App\Services;

use App\Models\QueueState;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Values\QueueState as QueueStateDTO;
use Illuminate\Support\Collection;

class QueueService
{
    private const DEFAULT_QUEUE_LIMIT = 500;

    public function __construct(private SongRepository $songRepository)
    {
    }

    public function getQueueState(User $user): QueueStateDTO
    {
        /** @var QueueState $state */
        $state = QueueState::query()->where('user_id', $user->id)->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'song_ids' => [],
        ]);

        $currentSong = $state->current_song_id ? $this->songRepository->findOne($state->current_song_id) : null;

        return QueueStateDTO::create(
            $this->songRepository->getByIds($state->song_ids, $user, true),
            $currentSong,
            $state->playback_position ?? 0
        );
    }

    /** @return Collection|array<array-key, Song> */
    public function generateRandomQueueSongs(User $user, int $limit = self::DEFAULT_QUEUE_LIMIT): Collection
    {
        $songs = $this->songRepository->getRandom($limit, $user);
        $this->replaceQueueContent($user, $songs);

        return $songs;
    }

    /** @return Collection|array<array-key, Song> */
    public function generateOrderedQueueSongs(
        User $user,
        string $sortColumn,
        string $sortDirection,
        int $limit = self::DEFAULT_QUEUE_LIMIT
    ): Collection {
        $songs = $this->songRepository->getForQueue($sortColumn, $sortDirection, $limit, $user);
        $this->replaceQueueContent($user, $songs);

        return $songs;
    }

    public function replaceQueueContent(User $user, Collection $songs): void
    {
        $this->updateQueueState($user, $songs->pluck('id')->toArray());
    }

    public function updateQueueState(User $user, array $songIds): void
    {
        QueueState::query()->updateOrCreate([
            'user_id' => $user->id,
        ], [
            'song_ids' => $songIds,
        ]);
    }

    public function updatePlaybackStatus(User $user, string $songId, int $position): void
    {
        QueueState::query()->updateOrCreate([
            'user_id' => $user->id,
        ], [
            'current_song_id' => $songId,
            'playback_position' => $position,
        ]);
    }
}
