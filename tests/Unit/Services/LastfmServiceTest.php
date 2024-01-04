<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Services\ApiClients\LastfmClient;
use App\Services\LastfmService;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;

class LastfmServiceTest extends TestCase
{
    private LastfmClient|MockInterface|LegacyMockInterface $client;
    private LastfmService $service;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.lastfm.key' => 'key',
            'koel.lastfm.secret' => 'secret',
        ]);

        $this->client = Mockery::mock(LastfmClient::class);
        $this->service = new LastfmService($this->client);
    }

    public function testGetArtistInformation(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->make(['name' => 'foo']);

        $this->client->shouldReceive('get')
            ->with('?method=artist.getInfo&autocorrect=1&artist=foo&format=json')
            ->once()
            ->andReturn(json_decode(File::get(__DIR__ . '/../../blobs/lastfm/artist.json')));

        $info = $this->service->getArtistInformation($artist);

        self::assertEquals([
            'url' => 'https://www.last.fm/music/Kamelot',
            'image' => null,
            'bio' => [
                'summary' => 'Quisque ut nisi.',
                'full' => 'Quisque ut nisi. Vestibulum ullamcorper mauris at ligula.',
            ],
        ], $info->toArray());
    }

    public function testGetArtistInformationForNonExistentArtist(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->make(['name' => 'bar']);

        $this->client->shouldReceive('get')
            ->with('?method=artist.getInfo&autocorrect=1&artist=bar&format=json')
            ->once()
            ->andReturn(json_decode(File::get(__DIR__ . '/../../blobs/lastfm/artist-notfound.json')));

        self::assertNull($this->service->getArtistInformation($artist));
    }

    public function testGetAlbumInformation(): void
    {
        /** @var Album $album */
        $album = Album::factory()->for(Artist::factory()->create(['name' => 'bar']))->create(['name' => 'foo']);

        $this->client->shouldReceive('get')
            ->with('?method=album.getInfo&autocorrect=1&album=foo&artist=bar&format=json')
            ->once()
            ->andReturn(json_decode(File::get(__DIR__ . '/../../blobs/lastfm/album.json')));

        $info = $this->service->getAlbumInformation($album);

        self::assertEquals([
            'url' => 'https://www.last.fm/music/Kamelot/Epica',
            'cover' => null,
            'tracks' => [
                [
                    'title' => 'Track 1',
                    'url' => 'https://foo/track1',
                    'length' => 100,
                ],
                [
                    'title' => 'Track 2',
                    'url' => 'https://foo/track2',
                    'length' => 150,
                ],
            ],
            'wiki' => [
                'summary' => 'Quisque ut nisi.',
                'full' => 'Quisque ut nisi. Vestibulum ullamcorper mauris at ligula.',
            ],
        ], $info->toArray());
    }

    public function testGetAlbumInformationForNonExistentAlbum(): void
    {
        /** @var Album $album */
        $album = Album::factory()->for(Artist::factory()->create(['name' => 'bar']))->create(['name' => 'foo']);

        $this->client->shouldReceive('get')
            ->with('?method=album.getInfo&autocorrect=1&album=foo&artist=bar&format=json')
            ->once()
            ->andReturn(json_decode(File::get(__DIR__ . '/../../blobs/lastfm/album-notfound.json')));

        self::assertNull($this->service->getAlbumInformation($album));
    }

    public function testScrobble(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'preferences' => [
                'lastfm_session_key' => 'my_key',
            ],
        ]);

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->client->shouldReceive('post')
            ->with('/', [
                'artist' => $song->artist->name,
                'track' => $song->title,
                'timestamp' => 100,
                'sk' => 'my_key',
                'method' => 'track.scrobble',
                'album' => $song->album->name,
            ], false)
            ->once();

        $this->service->scrobble($song, $user, 100);
    }

    /** @return array<mixed> */
    public function provideToggleLoveTrackData(): array
    {
        return [[true, 'track.love'], [false, 'track.unlove']];
    }

    /** @dataProvider provideToggleLoveTrackData */
    public function testToggleLoveTrack(bool $love, string $method): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'preferences' => [
                'lastfm_session_key' => 'my_key',
            ],
        ]);

        /** @var Song $song */
        $song = Song::factory()->for(Artist::factory()->create(['name' => 'foo']))->create(['title' => 'bar']);

        $this->client->shouldReceive('post')
            ->with('/', [
                'artist' => 'foo',
                'track' => 'bar',
                'sk' => 'my_key',
                'method' => $method,
            ], false)
            ->once();

        $this->service->toggleLoveTrack($song, $user, $love);
    }

    public function testUpdateNowPlaying(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'preferences' => [
                'lastfm_session_key' => 'my_key',
            ],
        ]);

        /** @var Song $song */
        $song = Song::factory()->for(Artist::factory()->create(['name' => 'foo']))->create(['title' => 'bar']);

        $this->client->shouldReceive('post')
            ->with('/', [
                'artist' => 'foo',
                'track' => 'bar',
                'duration' => $song->length,
                'sk' => 'my_key',
                'method' => 'track.updateNowPlaying',
                'album' => $song->album->name,
            ], false)
            ->once();

        $this->service->updateNowPlaying($song, $user);
    }
}
