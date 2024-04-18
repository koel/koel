<?php

namespace App\Services;

use App\Models\QueueState;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Values\QueueState as QueueStateDTO;

class QueueService
{
    public function __construct(private readonly SongRepository $songRepository)
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

        $currentSong = $state->current_song_id ? $this->songRepository->findOne($state->current_song_id, $user) : null;

        return QueueStateDTO::make(
            $this->songRepository->getMany(ids: $state->song_ids, inThatOrder: true, scopedUser: $user),
            $currentSong,
            $state->playback_position ?? 0
        );
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
