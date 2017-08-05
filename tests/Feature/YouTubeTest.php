<?php

namespace Tests\Feature;

use App\Models\Song;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use YouTube;

class YouTubeTest extends TestCase
{
    use WithoutMiddleware;

    /** @test */
    public function youtube_videos_related_to_a_song_can_be_searched()
    {
        $this->createSampleMediaSet();
        $song = Song::first();

        // We test on the facade here
        YouTube::shouldReceive('searchVideosRelatedToSong')->once();

        $this->getAsUser("/api/youtube/search/song/{$song->id}");
    }
}
