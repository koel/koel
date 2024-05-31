<?php

namespace Integration\Values;

use App\Models\Song;
use App\Values\Podcast\EpisodePlayable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EpisodePlayableTest extends TestCase
{
    public function testCreateAndRetrieved(): void
    {
        Http::fake([
            'https://example.com/episode.mp3' => Http::response('foo'),
        ]);

        $episode = Song::factory()->asEpisode()->create([
            'path' => 'https://example.com/episode.mp3',
        ]);

        $playable = EpisodePlayable::createForEpisode($episode);
        self::assertSame('acbd18db4cc2f85cedef654fccc4a4d8', $playable->checksum);

        self::assertTrue(Cache::has("episode-playable.$episode->id"));

        $retrieved = EpisodePlayable::retrieveForEpisode($episode);
        self::assertSame($playable, $retrieved);
        self::assertTrue($retrieved->valid());

        file_put_contents($playable->path, 'bar');
        self::assertFalse($retrieved->valid());
    }
}
