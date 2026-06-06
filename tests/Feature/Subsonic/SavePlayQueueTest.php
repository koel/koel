<?php

namespace Tests\Feature\Subsonic;

use App\Models\QueueState;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;

use function Tests\create_user;

class SavePlayQueueTest extends SubsonicTestCase
{
    #[Test]
    public function savesQueueWithCurrentSongAndPosition(): void
    {
        $user = create_user();
        $songs = Song::factory()->count(3)->create();

        $this->getSubsonic('savePlayQueue.view', $user, [
            'id' => $songs->pluck('id')->all(),
            'current' => $songs[1]->id,
            'position' => 12_345, // ms
            'c' => 'Feishin',
        ])->assertSubsonicOk();

        $state = QueueState::query()->where('user_id', $user->id)->firstOrFail();
        self::assertSame($songs->pluck('id')->all(), $state->song_ids);
        self::assertSame($songs[1]->id, $state->current_song_id);
        self::assertSame(12, $state->playback_position); // ms → s
        self::assertSame('Feishin', $state->changed_by);
    }

    #[Test]
    public function emptyParamsClearsTheQueue(): void
    {
        $user = create_user();
        QueueState::query()->create([
            'user_id' => $user->id,
            'song_ids' => ['old-1', 'old-2'],
            'playback_position' => 30,
        ]);

        $this->getSubsonic('savePlayQueue.view', $user)->assertSubsonicOk();

        $state = QueueState::query()->where('user_id', $user->id)->firstOrFail();
        self::assertSame([], $state->song_ids);
        self::assertNull($state->current_song_id);
        self::assertSame(0, $state->playback_position);
    }
}
