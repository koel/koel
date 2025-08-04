<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EpisodeTest extends TestCase
{
    #[Test]
    public function fetchEpisode(): void
    {
        /** @var Song $episode */
        $episode = Song::factory()->asEpisode()->create();

        $this->getAs("api/songs/{$episode->id}")
            ->assertJsonStructure(SongResource::JSON_STRUCTURE);
    }
}
