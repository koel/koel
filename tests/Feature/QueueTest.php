<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\QueueState;
use App\Models\Song;
use Tests\TestCase;

use function Tests\create_user;

class QueueTest extends TestCase
{
    public const QUEUE_STATE_JSON_STRUCTURE = [
        'current_song',
        'songs' => ['*' => SongResource::JSON_STRUCTURE],
        'playback_position',
    ];

    public function testGetEmptyState(): void
    {
        $this->getAs('api/queue/state')
            ->assertJsonStructure(self::QUEUE_STATE_JSON_STRUCTURE);
    }

    public function testGetExistingState(): void
    {
        /** @var QueueState $queueState */
        $queueState = QueueState::factory()->create([
            'current_song_id' => Song::factory(),
            'playback_position' => 123,
        ]);

        $this->getAs('api/queue/state', $queueState->user)
            ->assertJsonStructure(self::QUEUE_STATE_JSON_STRUCTURE);
    }

    public function testUpdateStateWithoutExistingState(): void
    {
        $user = create_user();

        self::assertDatabaseMissing(QueueState::class, ['user_id' => $user->id]);

        $songIds = Song::factory(3)->create()->pluck('id')->toArray();

        $this->putAs('api/queue/state', ['songs' => $songIds], $user)
            ->assertNoContent();

        /** @var QueueState $queue */
        $queue = QueueState::query()->where('user_id', $user->id)->firstOrFail();
        self::assertEqualsCanonicalizing($songIds, $queue->song_ids);
    }

    public function testUpdatePlaybackStatus(): void
    {
        /** @var QueueState $state */
        $state = QueueState::factory()->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->putAs('api/queue/playback-status', ['song' => $song->id, 'position' => 123], $state->user)
            ->assertNoContent();

        $state->refresh();
        self::assertSame($song->id, $state->current_song_id);
        self::assertSame(123, $state->playback_position);

        /** @var Song $anotherSong */
        $anotherSong = Song::factory()->create();

        $this->putAs('api/queue/playback-status', ['song' => $anotherSong->id, 'position' => 456], $state->user)
            ->assertNoContent();

        $state->refresh();
        self::assertSame($anotherSong->id, $state->current_song_id);
        self::assertSame(456, $state->playback_position);
    }

    public function testFetchSongs(): void
    {
        Song::factory(10)->create();

        $this->getAs('api/queue/fetch?order=rand&limit=5')
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE])
            ->assertJsonCount(5, '*');

        $this->getAs('api/queue/fetch?order=asc&sort=title&limit=5')
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE])
            ->assertJsonCount(5, '*');
    }
}
