<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\YouTubeService;
use Exception;
use Mockery;
use Mockery\MockInterface;

class YouTubeTest extends TestCase
{
    /** @var MockInterface */
    private $youTubeService;

    public function setUp(): void
    {
        parent::setUp();

        $this->youTubeService = $this->mockIocDependency(YouTubeService::class);
    }

    /**
     * @throws Exception
     */
    public function testSearchYouTubeVideos(): void
    {
        $this->createSampleMediaSet();
        $song = Song::first();

        $this->youTubeService
            ->shouldReceive('searchVideosRelatedToSong')
            ->with(Mockery::on(static function (Song $retrievedSong) use ($song) {
                return $song->id === $retrievedSong->id;
            }), 'foo')
            ->once();

        $this->getAsUser("/api/youtube/search/song/{$song->id}?pageToken=foo")
            ->assertResponseOk();
    }
}
