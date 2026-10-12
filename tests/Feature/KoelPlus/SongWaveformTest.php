<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use App\Models\SongAnalysis;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class SongWaveformTest extends PlusTestCase
{
    #[Test]
    public function fetchWaveform(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->createOne();
        SongAnalysis::factory()->for($song)->createOne(['waveform' => [0.1, 0.5, 1.0]]);

        $this
            ->getAs("api/songs/{$song->id}/waveform", $user)
            ->assertOk()
            ->assertExactJson(['waveform' => [0.1, 0.5, 1.0]])
            ->assertHeader('ETag')
            ->assertHeader('Cache-Control', 'no-cache, private');
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
        SongAnalysis::factory()->for($song)->createOne();

        $this->getAs("api/songs/{$song->id}/waveform")->assertForbidden();
    }
}
