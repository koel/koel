<?php

namespace Tests\Feature\V6;

use App\Models\Interaction;

class PlayCountTest extends TestCase
{
    public function testStore(): void
    {
        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create([
            'play_count' => 10,
        ]);

        $response = $this->postAs('/api/interaction/play', [
            'song' => $interaction->song->id,
        ], $interaction->user);

        $response->assertJsonStructure([
            'type',
            'id',
            'song_id',
            'liked',
            'play_count',
        ]);

        self::assertEquals(11, $interaction->refresh()->play_count);
    }
}
