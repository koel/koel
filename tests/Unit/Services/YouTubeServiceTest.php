<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Song;
use App\Services\ApiClients\YouTubeClient;
use App\Services\YouTubeService;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class YouTubeServiceTest extends TestCase
{
    public function testSearchVideosRelatedToSong(): void
    {
        /** @var Song $song */
        $song = Song::factory()->for(Artist::factory()->create(['name' => 'Bar']))->create(['title' => 'Foo']);
        $client = Mockery::mock(YouTubeClient::class);

        $client->shouldReceive('get')
            ->with('search?part=snippet&type=video&maxResults=10&pageToken=my-token&q=Foo+Bar')
            ->andReturn(json_decode(File::get(__DIR__ . '/../../blobs/youtube/search.json')));

        $service = new YouTubeService($client, app(Repository::class));
        $response = $service->searchVideosRelatedToSong($song, 'my-token');

        self::assertSame('Slipknot - Snuff [OFFICIAL VIDEO]', $response->items[0]->snippet->title);
        self::assertNotNull(cache()->get('5becf539115b18b2df11c39adbc2bdfa'));
    }
}
