<?php

namespace Tests\Unit\Services\SongStorages;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\SongStorages\S3CompatibleStorage;
use Tests\TestCase;

class S3CompatibleStorageTest extends TestCase
{
    public function testSupported(): void
    {
        $this->expectException(KoelPlusRequiredException::class);

        /** @var Song $song */
        $song = Song::factory()->create();

        /** @var S3CompatibleStorage $service */
        $service = app(S3CompatibleStorage::class);
        $service->getSongPresignedUrl($song);
    }
}
