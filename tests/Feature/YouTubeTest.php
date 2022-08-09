<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\YouTubeService;
use Mockery;
use Mockery\MockInterface;

class YouTubeTest extends TestCase
{
    private MockInterface $youTubeService;

    public function setUp(): void
    {
        parent::setUp();

        $this->youTubeService = self::mock(YouTubeService::class);
    }

    public function testSearchYouTubeVideos(): void
    {
        static::createSampleMediaSet();

        /** @var Song $song */
        $song = Song::query()->first();

        $this->youTubeService
            ->shouldReceive('searchVideosRelatedToSong')
            ->with(Mockery::on(static fn (Song $retrievedSong) => $song->is($retrievedSong)), 'foo')
            ->once();

        $this->getAs("/api/youtube/search/song/{$song->id}?pageToken=foo")
            ->assertOk();
    }
}
