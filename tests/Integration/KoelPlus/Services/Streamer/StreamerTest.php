<?php

namespace Tests\Integration\KoelPlus\Services\Streamer;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\Streamer\Adapters\DropboxStreamerAdapter;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Streamer;
use Illuminate\Support\Facades\File;
use Tests\PlusTestCase;

class StreamerTest extends PlusTestCase
{
    public function testResolveAdapters(): void
    {
        File::partialMock()->shouldReceive('mimeType')->andReturn('audio/mpeg');

        collect(SongStorageType::cases())
            ->each(static function (SongStorageType $type): void {
                /** @var Song $song */
                $song = Song::factory()->make(['storage' => $type]);
                $streamer = new Streamer($song);

                switch ($type) {
                    case SongStorageType::S3:
                    case SongStorageType::S3_LAMBDA:
                        self::assertInstanceOf(S3CompatibleStreamerAdapter::class, $streamer->getAdapter());
                        break;

                    case SongStorageType::DROPBOX:
                        self::assertInstanceOf(DropboxStreamerAdapter::class, $streamer->getAdapter());
                        break;

                    case SongStorageType::LOCAL:
                        self::assertInstanceOf(LocalStreamerAdapter::class, $streamer->getAdapter());
                        break;
                }
            });
    }
}
