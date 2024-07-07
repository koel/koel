<?php

namespace Tests\Integration\Services;

use App\Http\Integrations\Lastfm\Requests\GetAlbumInfoRequest;
use App\Http\Integrations\Lastfm\Requests\GetArtistInfoRequest;
use App\Http\Integrations\Lastfm\Requests\ScrobbleRequest;
use App\Http\Integrations\Lastfm\Requests\ToggleLoveTrackRequest;
use App\Http\Integrations\Lastfm\Requests\UpdateNowPlayingRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\LastfmService;
use Illuminate\Support\Facades\File;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class LastfmServiceTest extends TestCase
{
    private LastfmService $service;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.lastfm.key' => 'key',
            'koel.lastfm.secret' => 'secret',
        ]);

        $this->service = app(LastfmService::class);
    }

    public function testGetArtistInformation(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->make(['name' => 'Kamelot']);

        Saloon::fake([
            GetArtistInfoRequest::class => MockResponse::make(body: File::get(test_path('blobs/lastfm/artist.json'))),
        ]);

        $info = $this->service->getArtistInformation($artist);

        Saloon::assertSent(static function (GetArtistInfoRequest $request): bool {
            self::assertSame([
                'method' => 'artist.getInfo',
                'artist' => 'Kamelot',
                'autocorrect' => 1,
                'format' => 'json',
            ], $request->query()->all());

            return true;
        });

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

        Saloon::fake([
            GetArtistInfoRequest::class => MockResponse::make(
                body: File::get(test_path('blobs/lastfm/artist-notfound.json'))
            ),
        ]);

        self::assertNull($this->service->getArtistInformation($artist));
    }

    public function testGetAlbumInformation(): void
    {
        /** @var Album $album */
        $album = Album::factory()->for(Artist::factory()->create(['name' => 'Kamelot']))->create(['name' => 'Epica']);

        Saloon::fake([
            GetAlbumInfoRequest::class => MockResponse::make(body: File::get(test_path('blobs/lastfm/album.json'))),
        ]);

        $info = $this->service->getAlbumInformation($album);

        Saloon::assertSent(static function (GetAlbumInfoRequest $request): bool {
            self::assertSame([
                'method' => 'album.getInfo',
                'artist' => 'Kamelot',
                'album' => 'Epica',
                'autocorrect' => 1,
                'format' => 'json',
            ], $request->query()->all());

            return true;
        });

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
        $album = Album::factory()->for(Artist::factory()->create(['name' => 'Kamelot']))->create(['name' => 'Foo']);

        Saloon::fake([
            GetAlbumInfoRequest::class => MockResponse::make(
                body: File::get(test_path('blobs/lastfm/album-notfound.json'))
            ),
        ]);

        self::assertNull($this->service->getAlbumInformation($album));
    }

    public function testScrobble(): void
    {
        $user = create_user([
            'preferences' => [
                'lastfm_session_key' => 'my_key',
            ],
        ]);

        /** @var Song $song */
        $song = Song::factory()->create();

        Saloon::fake([ScrobbleRequest::class => MockResponse::make()]);

        $this->service->scrobble($song, $user, 100);

        Saloon::assertSent(static function (ScrobbleRequest $request) use ($song): bool {
            self::assertSame([
                'method' => 'track.scrobble',
                'artist' => $song->artist->name,
                'track' => $song->title,
                'timestamp' => 100,
                'sk' => 'my_key',
                'album' => $song->album->name,
            ], $request->body()->all());

            return true;
        });
    }

    /** @return array<mixed> */
    public static function provideToggleLoveTrackData(): array
    {
        return [[true, 'track.love'], [false, 'track.unlove']];
    }

    /** @dataProvider provideToggleLoveTrackData */
    public function testToggleLoveTrack(bool $love, string $method): void
    {
        $user = create_user([
            'preferences' => [
                'lastfm_session_key' => 'my_key',
            ],
        ]);

        /** @var Song $song */
        $song = Song::factory()->create();

        Saloon::fake([ToggleLoveTrackRequest::class => MockResponse::make()]);

        $this->service->toggleLoveTrack($song, $user, $love);

        Saloon::assertSent(static function (ToggleLoveTrackRequest $request) use ($song, $love): bool {
            self::assertSame([
                'method' => $love ? 'track.love' : 'track.unlove',
                'sk' => 'my_key',
                'artist' => $song->artist->name,
                'track' => $song->title,
            ], $request->body()->all());

            return true;
        });
    }

    public function testUpdateNowPlaying(): void
    {
        $user = create_user([
            'preferences' => [
                'lastfm_session_key' => 'my_key',
            ],
        ]);

        /** @var Song $song */
        $song = Song::factory()->for(Artist::factory()->create(['name' => 'foo']))->create(['title' => 'bar']);

        Saloon::fake([UpdateNowPlayingRequest::class => MockResponse::make()]);

        $this->service->updateNowPlaying($song, $user);

        Saloon::assertSent(static function (UpdateNowPlayingRequest $request) use ($song): bool {
            self::assertSame([
                'method' => 'track.updateNowPlaying',
                'artist' => $song->artist->name,
                'track' => $song->title,
                'duration' => $song->length,
                'sk' => 'my_key',
                'album' => $song->album->name,
            ], $request->body()->all());

            return true;
        });
    }
}
