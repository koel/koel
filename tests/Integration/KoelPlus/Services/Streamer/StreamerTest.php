<?php

namespace Tests\Integration\KoelPlus\Services\Streamer;

use App\Models\Song;
use App\Services\Streamer\Adapters\DropboxStreamerAdapter;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Streamer;
use App\Values\SongStorageTypes;
use Exception;
use Illuminate\Support\Facades\File;
use Tests\PlusTestCase;

class StreamerTest extends PlusTestCase
{
    public function testResolveAdapters(): void
    {
        File::partialMock()->shouldReceive('mimeType')->andReturn('audio/mpeg');

        collect(SongStorageTypes::ALL_TYPES)
            ->each(static function (?string $type): void {
                /** @var Song $song */
                $song = Song::factory()->make(['storage' => $type]);
                $streamer = new Streamer($song);

                switch ($type) {
                    case SongStorageTypes::S3:
                    case SongStorageTypes::S3_LAMBDA:
                        self::assertInstanceOf(S3CompatibleStreamerAdapter::class, $streamer->getAdapter());
                        break;

                    case SongStorageTypes::DROPBOX:
                        self::assertInstanceOf(DropboxStreamerAdapter::class, $streamer->getAdapter());
                        break;

                    case SongStorageTypes::LOCAL:
                    case '':
                        self::assertInstanceOf(LocalStreamerAdapter::class, $streamer->getAdapter());
                        break;

                    default:
                        throw new Exception("Unhandled storage type: $type");
                }
            });
    }
}
