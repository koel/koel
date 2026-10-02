<?php

namespace Tests\Feature;

use App\Models\SongWaveform;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongWaveformTest extends TestCase
{
    #[Test]
    public function notAvailableWithoutKoelPlus(): void
    {
        $waveform = SongWaveform::factory()->createOne();

        $this->getAs("api/songs/{$waveform->song_id}/waveform")->assertNotFound();
    }
}
