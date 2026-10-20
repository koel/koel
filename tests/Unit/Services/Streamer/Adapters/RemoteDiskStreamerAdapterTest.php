<?php

namespace Tests\Unit\Services\Streamer\Adapters;

use App\Enums\SongStorageType;
use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use App\Services\Streamer\Adapters\RemoteDiskStreamerAdapter;
use Illuminate\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class RemoteDiskStreamerAdapterTest extends TestCase
{
    #[Test]
    public function refusesToStreamWithoutKoelPlus(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::SFTP, 'path' => 'sftp://song.mp3']);
        $storage = Mockery::mock(SftpStorage::class);
        $storage->makePartial();
        $storage->shouldNotReceive('copyToLocal');

        $this->expectException(KoelPlusRequiredException::class);

        (new RemoteDiskStreamerAdapter($storage))->stream($song);
    }

    #[Test]
    public function deleteTheTemporaryCopyOnceStreamed(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::SFTP, 'path' => 'sftp://song.mp3']);
        $temporaryCopy = artifact_path('tmp/' . uniqid() . '.mp3');
        File::copy(test_path('songs/blank.mp3'), $temporaryCopy);

        $storage = Mockery::mock(SftpStorage::class);
        $storage->expects('assertSupported');
        $storage->expects('copyToLocal')->with('song.mp3')->andReturn($temporaryCopy);

        $response = (new RemoteDiskStreamerAdapter($storage))->stream($song);

        ob_start();
        $response->sendContent();
        ob_end_clean();

        self::assertFileDoesNotExist($temporaryCopy);
    }
}
