<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Filesystems\DropboxFilesystem;
use App\Models\Song;
use App\Services\SongStorages\DropboxStorage;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class DropboxStorageTest extends PlusTestCase
{
    private MockInterface|DropboxFilesystem $filesystem;
    private MockInterface|Client $client;
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.storage_driver' => 'dropbox',
            'filesystems.disks.dropbox' => [
                'app_key' => 'dropbox-key',
                'app_secret' => 'dropbox-secret',
                'refresh_token' => 'coca-cola',
            ],
        ]);

        $this->client = $this->mock(Client::class);
        $this->filesystem = $this->mock(DropboxFilesystem::class);

        $this->filesystem->allows('getAdapter')->andReturns(
            Mockery::mock(DropboxAdapter::class, ['getClient' => $this->client])
        );

        self::mockRefreshAccessTokenCall();

        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    public function testSupported(): void
    {
        $this->client->allows('setAccessToken');

        self::assertTrue(app(DropboxStorage::class)->supported());
    }

    public function testStoreUploadedFile(): void
    {
        $this->client->shouldReceive('setAccessToken')->with('free-bird')->once();

        /** @var DropboxStorage $service */
        $service = app(DropboxStorage::class);

        Http::assertSent(static function (Request $request) {
            return $request->hasHeader('Authorization', 'Basic ' . base64_encode('dropbox-key:dropbox-secret'))
                && $request->isForm()
                && $request['refresh_token'] === 'coca-cola'
                && $request['grant_type'] === 'refresh_token';
        });

        self::assertSame(0, Song::query()->where('storage', 'dropbox')->count());

        $this->filesystem->shouldReceive('write')->once();
        $service->storeUploadedFile($this->file, create_user());

        self::assertSame(1, Song::query()->where('storage', 'dropbox')->count());
        self::assertSame('free-bird', Cache::get('dropbox_access_token'));
    }

    public function testAccessTokenCache(): void
    {
        Cache::put('dropbox_access_token', 'cached-token', now()->addHour());

        $this->client->shouldReceive('setAccessToken')->with('cached-token')->once();
        app(DropboxStorage::class);

        self::assertSame('cached-token', Cache::get('dropbox_access_token'));
        Http::assertNothingSent();
    }

    public function testGetSongPresignedUrl(): void
    {
        $this->client->allows('setAccessToken');

        /** @var Song $song */
        $song = Song::factory()->create(['path' => 'dropbox://song.mp3', 'storage' => 'dropbox']);

        /** @var DropboxStorage $service */
        $service = app(DropboxStorage::class);

        $this->filesystem->shouldReceive('temporaryUrl')
            ->once()
            ->with('song.mp3')
            ->andReturn('https://dropbox.com/song.mp3?token=123');

        self::assertSame('https://dropbox.com/song.mp3?token=123', $service->getSongPresignedUrl($song));
    }

    private static function mockRefreshAccessTokenCall(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.dropboxapi.com/oauth2/token' => Http::response([
                'access_token' => 'free-bird',
                'expires_in' => 3600,
            ]),
        ]);
    }
}
