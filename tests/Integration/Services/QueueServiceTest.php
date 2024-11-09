<?php

namespace Tests\Integration\Services;

use App\Models\QueueState;
use App\Models\Song;
use App\Services\QueueService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class QueueServiceTest extends TestCase
{
    private QueueService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(QueueService::class);
    }

    #[Test]
    public function getQueueState(): void
    {
        /** @var Song $currentSong */
        $currentSong = Song::factory()->create();

        /** @var QueueState $state */
        $state = QueueState::factory()->create([
            'current_song_id' => $currentSong->id,
            'playback_position' => 123,
        ]);

        $dto = $this->service->getQueueState($state->user);

        self::assertEqualsCanonicalizing($state->song_ids, $dto->playables->pluck('id')->toArray());
        self::assertSame($currentSong->id, $dto->currentPlayable->id);
        self::assertSame(123, $dto->playbackPosition);
    }

    #[Test]
    public function createQueueState(): void
    {
        $user = create_user();

        $this->assertDatabaseMissing(QueueState::class, [
            'user_id' => $user->id,
        ]);

        $songIds = Song::factory()->count(3)->create()->modelKeys();
        $this->service->updateQueueState($user, $songIds);

        /** @var QueueState $queueState */
        $queueState = QueueState::query()->whereBelongsTo($user)->firstOrFail();
        self::assertEqualsCanonicalizing($songIds, $queueState->song_ids);
        self::assertNull($queueState->current_song_id);
        self::assertSame(0, $queueState->playback_position);
    }

    #[Test]
    public function updateQueueState(): void
    {
        /** @var QueueState $state */
        $state = QueueState::factory()->create();

        $songIds = Song::factory()->count(3)->create()->modelKeys();
        $this->service->updateQueueState($state->user, $songIds);

        $state->refresh();

        self::assertEqualsCanonicalizing($songIds, $state->song_ids);
        self::assertNull($state->current_song_id);
        self::assertEquals(0, $state->playback_position);
    }

    #[Test]
    public function updatePlaybackStatus(): void
    {
        /** @var QueueState $state */
        $state = QueueState::factory()->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->service->updatePlaybackStatus($state->user, $song, 123);
        $state->refresh();

        self::assertSame($song->id, $state->current_song_id);
        self::assertSame(123, $state->playback_position);
    }
}
