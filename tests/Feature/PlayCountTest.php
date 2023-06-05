<?php

namespace Tests\Feature;

use App\Events\SongStartedPlaying;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Facades\Event;

class PlayCountTest extends TestCase
{
    public function testStoreExistingEntry(): void
    {
        Event::fake(SongStartedPlaying::class);

        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create([
            'play_count' => 10,
        ]);

        $this->postAs('/api/interaction/play', ['song' => $interaction->song->id], $interaction->user)
            ->assertJsonStructure([
                'type',
                'id',
                'song_id',
                'liked',
                'play_count',
            ]);

        self::assertSame(11, $interaction->refresh()->play_count);
        Event::assertDispatched(SongStartedPlaying::class);
    }

    public function testStoreNewEntry(): void
    {
        Event::fake(SongStartedPlaying::class);

        /** @var Song $song */
        $song = Song::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->postAs('/api/interaction/play', ['song' => $song->id], $user)
            ->assertJsonStructure([
                'type',
                'id',
                'song_id',
                'liked',
                'play_count',
            ]);

        /** @var Interaction $interaction */
        $interaction = Interaction::query()
            ->where('song_id', $song->id)
            ->where('user_id', $user->id)
            ->first();

        self::assertSame(1, $interaction->play_count);
        Event::assertDispatched(SongStartedPlaying::class);
    }
}
