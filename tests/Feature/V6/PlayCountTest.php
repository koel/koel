<?php

namespace Tests\Feature\V6;

use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;

class PlayCountTest extends TestCase
{
    public function testStoreExistingEntry(): void
    {
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

        self::assertEquals(11, $interaction->refresh()->play_count);
    }

    public function testStoreNewEntry(): void
    {
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

        self::assertEquals(1, Interaction::whereSongIdAndUserId($song->id, $user->id)->first()->play_count);
    }
}
