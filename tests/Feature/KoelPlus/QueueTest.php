<?php

namespace Tests\Feature\KoelPlus;

use App\Models\QueueState;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class QueueTest extends PlusTestCase
{
    #[Test]
    public function cannotUpdatePlaybackStatusWithInaccessibleSong(): void
    {
        $state = QueueState::factory()->createOne();
        $song = Song::factory()->private()->createOne();

        $this->putAs(
            'api/queue/playback-status',
            ['song' => $song->id, 'position' => 123],
            $state->user,
        )->assertNotFound();

        self::assertNotSame($song->id, $state->refresh()->current_song_id);
    }
}
