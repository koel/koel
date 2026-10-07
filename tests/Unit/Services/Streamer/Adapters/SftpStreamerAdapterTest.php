<?php

namespace Tests\Unit\Services\Streamer\Adapters;

use App\Enums\SongStorageType;
use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use App\Services\Streamer\Adapters\SftpStreamerAdapter;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SftpStreamerAdapterTest extends TestCase
{
    #[Test]
    public function refusesToStreamWithoutKoelPlus(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::SFTP, 'path' => 'sftp://song.mp3']);
        $storage = Mockery::mock(SftpStorage::class);
        $storage->makePartial();
        $storage->shouldNotReceive('copyToLocal');

        $this->expectException(KoelPlusRequiredException::class);

        (new SftpStreamerAdapter($storage))->stream($song);
    }
}
