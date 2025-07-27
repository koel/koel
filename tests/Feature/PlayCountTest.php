<?php

namespace Tests\Feature;

use App\Events\PlaybackStarted;
use App\Models\Interaction;
use App\Models\Song;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlayCountTest extends TestCase
{
    #[Test]
    public function storeExistingEntry(): void
    {
        Event::fake(PlaybackStarted::class);

        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create(['play_count' => 10]);

        $this->postAs('/api/interaction/play', ['song' => $interaction->song_id], $interaction->user)
            ->assertJsonStructure([
                'type',
                'id',
                'song_id',
                'play_count',
            ]);

        self::assertSame(11, $interaction->refresh()->play_count);
        Event::assertDispatched(PlaybackStarted::class);
    }

    #[Test]
    public function storeNewEntry(): void
    {
        Event::fake(PlaybackStarted::class);

        /** @var Song $song */
        $song = Song::factory()->create();
        $user = create_user();

        $this->postAs('/api/interaction/play', ['song' => $song->id], $user)
            ->assertJsonStructure([
                'type',
                'id',
                'song_id',
                'play_count',
            ]);

        $interaction = Interaction::query()
            ->whereBelongsTo($song)
            ->whereBelongsTo($user)
            ->first();

        self::assertSame(1, $interaction->play_count);
        Event::assertDispatched(PlaybackStarted::class);
    }
}
