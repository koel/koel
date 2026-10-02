<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use App\Models\SongWaveform;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class SongWaveformTest extends PlusTestCase
{
    #[Test]
    public function fetchLevels(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->createOne();
        SongWaveform::factory()->for($song)->createOne(['levels' => [0.1, 0.5, 1.0]]);

        $this
            ->getAs("api/songs/{$song->id}/waveform", $user)
            ->assertOk()
            ->assertExactJson(['levels' => [0.1, 0.5, 1.0]])
            ->assertHeader('ETag');
    }

    #[Test]
    public function notFoundForAnUnanalyzedSong(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->createOne();

        $this->getAs("api/songs/{$song->id}/waveform", $user)->assertNotFound();
    }

    #[Test]
    public function forbiddenForAnInaccessibleSong(): void
    {
        $song = Song::factory()->private()->createOne();
        SongWaveform::factory()->for($song)->createOne();

        $this->getAs("api/songs/{$song->id}/waveform")->assertForbidden();
    }
}
