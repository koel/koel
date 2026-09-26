<?php

namespace Tests\Feature\KoelPlus;

use App\Jobs\HandlePresignedSongUploadJob;
use App\Models\Song;
use App\Responses\SongUploadResponse;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\S3UploadUrlSigner;
use App\Services\SongStorages\SongStorage;
use App\Values\PresignedUpload;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class PresignedUploadTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['koel.storage_driver' => 's3']);
        Storage::fake('s3');
    }

    #[Test]
    public function presignAnUpload(): void
    {
        $user = create_user();

        $this
            ->mock(S3UploadUrlSigner::class)
            ->expects('sign')
            ->with(
                Mockery::on(static fn (string $key): bool => str_starts_with($key, "{$user->public_id}__")),
                1234,
                Mockery::any(),
            )
            ->andReturnUsing(static fn (
                string $key,
                int $size,
                Carbon $expiresAt,
            ): PresignedUpload => PresignedUpload::make(
                key: $key,
                url: "https://storage.example.com/$key",
                headers: [],
                expiresAt: $expiresAt,
            ));

        $this
            ->postAs('api/upload/presign', ['file_name' => 'song.mp3', 'file_size' => 1234], $user)
            ->assertOk()
            ->assertJsonStructure(['key', 'url', 'headers', 'expires_at']);
    }

    #[Test]
    public function presigningRequiresAFileName(): void
    {
        $this->postAs('api/upload/presign', ['file_size' => 1234], create_user())->assertUnprocessable();
    }

    #[Test]
    public function presigningRequiresAFileSize(): void
    {
        $this->postAs('api/upload/presign', ['file_name' => 'song.mp3'], create_user())->assertUnprocessable();
    }

    #[Test]
    public function presigningRefusesAFileLargerThanTheUploadLimit(): void
    {
        $this->postAs(
            'api/upload/presign',
            ['file_name' => 'song.mp3', 'file_size' => UploadedFile::getMaxFilesize() + 1],
            create_user(),
        )->assertUnprocessable();
    }

    #[Test]
    public function presigningRefusesSomethingThatIsNotAudio(): void
    {
        $this->postAs(
            'api/upload/presign',
            ['file_name' => 'invoice.pdf', 'file_size' => 1234],
            create_user(),
        )->assertUnprocessable();
    }

    #[Test]
    public function refusesToCompleteAnObjectLargerThanTheUploadLimit(): void
    {
        $user = create_user();
        $key = "{$user->public_id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));

        $storage = Mockery::mock(S3CompatibleStorage::class . '[sizeOfUpload]', [
            Mockery::mock(S3UploadUrlSigner::class),
            'koel',
        ]);
        $storage->shouldReceive('sizeOfUpload')->andReturn(UploadedFile::getMaxFilesize() + 1);
        $this->app->instance(SongStorage::class, $storage);

        $this->postAs(
            'api/upload/complete',
            ['key' => $key],
            $user,
        )->assertStatus(Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
    }

    #[Test]
    public function completeAnUpload(): void
    {
        $user = create_user();
        $key = "{$user->public_id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));
        $this->fetchesTheObjectAsALocalCopy();

        $this
            ->postAs('api/upload/complete', ['key' => $key], $user)
            ->assertOk()
            ->assertJsonStructure(['song', 'album']);

        /** @var Song $song */
        $song = Song::query()->latest()->first();
        self::assertSame("s3://koel/$key", $song->path);
        self::assertSame($user->id, $song->owner_id);
    }

    private function fetchesTheObjectAsALocalCopy(): void
    {
        $storage = Mockery::mock(S3CompatibleStorage::class . '[getLocalPath]', [
            Mockery::mock(S3UploadUrlSigner::class),
            'koel',
        ]);

        $storage
            ->shouldReceive('getLocalPath')
            ->andReturnUsing(static function (): string {
                $localPath = artifact_path('tmp/' . uniqid() . '.mp3');
                File::copy(test_path('songs/full.mp3'), $localPath);

                return $localPath;
            });

        $this->app->instance(SongStorage::class, $storage);
    }

    #[Test]
    public function doesNotBroadcastWhenTheCallerAlreadyGetsTheSong(): void
    {
        Event::fake(SongUploadResponse::class);

        $user = create_user();
        $key = "{$user->public_id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));
        $this->fetchesTheObjectAsALocalCopy();

        $this
            ->postAs('api/upload/complete', ['key' => $key], $user)
            ->assertOk()
            ->assertJsonStructure(['song', 'album']);

        Event::assertNotDispatched(SongUploadResponse::class);
    }

    #[Test]
    public function refusesToCompleteTheSameUploadTwice(): void
    {
        $user = create_user();
        $key = "{$user->public_id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));
        $this->fetchesTheObjectAsALocalCopy();

        $this->postAs('api/upload/complete', ['key' => $key], $user)->assertOk();
        $this->postAs('api/upload/complete', ['key' => $key], $user)->assertConflict();

        self::assertSame(1, Song::query()->where('path', "s3://koel/$key")->count());
    }

    #[Test]
    public function refusesASecondCompletionWhileTheFirstIsStillQueued(): void
    {
        $user = create_user();
        $key = "{$user->public_id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));

        Cache::add(HandlePresignedSongUploadJob::claimKeyFor("s3://koel/$key"), true, now()->addHour());

        $this->postAs('api/upload/complete', ['key' => $key], $user)->assertConflict();
    }

    #[Test]
    public function cannotCompleteSomeoneElsesUpload(): void
    {
        $someoneElse = create_user();
        $key = "{$someoneElse->public_id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));

        $this->postAs('api/upload/complete', ['key' => $key], create_user())->assertForbidden();
    }
}
