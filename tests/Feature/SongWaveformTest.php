<?php

namespace Tests\Feature;

use App\Models\SongAnalysis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongWaveformTest extends TestCase
{
    #[Test]
    public function notAvailableWithoutKoelPlus(): void
    {
        $analysis = SongAnalysis::factory()->createOne();

        $this->getAs("api/songs/{$analysis->song_id}/waveform")->assertNotFound();
    }
}
