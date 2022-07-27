<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\YouTubeService;
use Mockery;

class YouTubeTest extends TestCase
{
    private $youTubeService;

    public function setUp(): void
    {
        parent::setUp();

        $this->youTubeService = self::mock(YouTubeService::class);
    }

    public function testSearchYouTubeVideos(): void
    {
        static::createSampleMediaSet();
        $song = Song::first();

        $this->youTubeService
            ->shouldReceive('searchVideosRelatedToSong')
            ->with(Mockery::on(static function (Song $retrievedSong) use ($song) {
                return $song->id === $retrievedSong->id;
            }), 'foo')
            ->once();

        $this->getAs("/api/youtube/search/song/{$song->id}?pageToken=foo")
            ->assertOk();
    }
}
