<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\SongStorage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
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

        $response = $this
            ->postAs('api/upload/presign', ['file_name' => 'song.mp3'], $user)
            ->assertOk()
            ->assertJsonStructure(['key', 'url', 'headers', 'expires_at']);

        self::assertStringStartsWith("{$user->id}__", $response->json('key'));
    }

    #[Test]
    public function presigningRequiresAFileName(): void
    {
        $this->postAs('api/upload/presign', [], create_user())->assertUnprocessable();
    }

    #[Test]
    public function completeAnUpload(): void
    {
        $user = create_user();
        $key = "{$user->id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));
        $this->fetchesTheObjectAsALocalCopy();

        $this->postAs('api/upload/complete', ['key' => $key], $user)->assertSuccessful();

        /** @var Song $song */
        $song = Song::query()->latest()->first();
        self::assertSame("s3://koel/$key", $song->path);
        self::assertSame($user->id, $song->owner_id);
    }

    private function fetchesTheObjectAsALocalCopy(): void
    {
        $storage = Mockery::mock(S3CompatibleStorage::class . '[getLocalPath]', ['koel']);

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
    public function cannotCompleteSomeoneElsesUpload(): void
    {
        $someoneElse = create_user();
        $key = "{$someoneElse->id}__random__song.mp3";
        Storage::disk('s3')->put($key, File::get(test_path('songs/full.mp3')));

        $this->postAs('api/upload/complete', ['key' => $key], create_user())->assertForbidden();
    }
}
