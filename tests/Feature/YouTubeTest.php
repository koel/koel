<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\YouTubeService;
use Exception;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery\MockInterface;

class YouTubeTest extends TestCase
{
    use WithoutMiddleware;

    /** @var YouTubeService|MockInterface */
    private $youTubeService;

    public function setUp()
    {
        parent::setUp();

        $this->youTubeService = $this->mockIocDependency(YouTubeService::class);
    }

    /**
     * @throws Exception
     */
    public function testSearchYouTubeVideos()
    {
        $this->createSampleMediaSet();
        $song = Song::first();

        $this->youTubeService
            ->shouldReceive('searchVideosRelatedToSong')
            ->once();

        $this->getAsUser("/api/youtube/search/song/{$song->id}");
    }
}
