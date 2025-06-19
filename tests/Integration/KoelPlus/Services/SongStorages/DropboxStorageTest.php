<?php

namespace Tests\Integration\KoelPlus\Services\SongStorages;

use App\Filesystems\DropboxFilesystem;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Services\SongStorages\DropboxStorage;
use App\Values\UploadReference;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Tests\Integration\KoelPlus\Services\TestingDropboxStorage;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class DropboxStorageTest extends PlusTestCase
{
    use TestingDropboxStorage;

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

        $this->filesystem->allows('getAdapter')->andReturn(
            Mockery::mock(DropboxAdapter::class, ['getClient' => $this->client])
        );

        self::mockDropboxRefreshAccessTokenCall();

        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    #[Test]
    public function storeUploadedFile(): void
    {
        Ulid::freeze('random');

        $this->client->shouldReceive('setAccessToken')->with('free-bird')->once();

        /** @var DropboxStorage $service */
        $service = app(DropboxStorage::class);

        Http::assertSent(static function (Request $request) {
            return $request->hasHeader('Authorization', 'Basic ' . base64_encode('dropbox-key:dropbox-secret'))
                && $request->isForm()
                && $request['refresh_token'] === 'coca-cola'
                && $request['grant_type'] === 'refresh_token';
        });

        $user = create_user();
        $this->filesystem->shouldReceive('write')->once();
        $reference = $service->storeUploadedFile($this->file, $user);

        self::assertSame("dropbox://{$user->id}__random__song.mp3", $reference->location);
        self::assertSame(artifact_path("tmp/random/song.mp3"), $reference->localPath);

        self::assertSame('free-bird', Cache::get('dropbox_access_token'));
    }

    #[Test]
    public function undoUpload(): void
    {
        $this->filesystem->shouldReceive('delete')->once()->with('koel/song.mp3');
        File::shouldReceive('delete')->once()->with('/tmp/random/song.mp3');

        $reference = UploadReference::make(
            location: 'dropbox://koel/song.mp3',
            localPath: '/tmp/random/song.mp3',
        );

        $this->client->shouldReceive('setAccessToken')->with('free-bird')->once();

        /** @var DropboxStorage $service */
        $service = app(DropboxStorage::class);

        $service->undoUpload($reference);
    }

    #[Test]
    public function accessTokenCache(): void
    {
        Cache::put('dropbox_access_token', 'cached-token', now()->addHour());

        $this->client->shouldReceive('setAccessToken')->with('cached-token')->once();
        app(DropboxStorage::class);

        self::assertSame('cached-token', Cache::get('dropbox_access_token'));
        Http::assertNothingSent();
    }

    #[Test]
    public function getSongPresignedUrl(): void
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

        self::assertSame(
            'https://dropbox.com/song.mp3?token=123',
            $service->getPresignedUrl($song->storage_metadata->getPath())
        );
    }
}
