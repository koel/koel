<?php

namespace App\Services;

use App\Models\QueueState;
use App\Models\Song;
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
        $state = QueueState::query()->where('user_id', $user->id)->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'song_ids' => [],
        ]);

        $currentSong = $state->current_song_id ? $this->songRepository->findOne($state->current_song_id, $user) : null;

        return QueueStateDTO::make(
            $this->songRepository->getMany(ids: $state->song_ids, preserveOrder: true, scopedUser: $user),
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

    public function updatePlaybackStatus(User $user, Song $song, int $position): void
    {
        QueueState::query()->updateOrCreate([
            'user_id' => $user->id,
        ], [
            'current_song_id' => $song->id,
            'playback_position' => $position,
        ]);
    }
}
