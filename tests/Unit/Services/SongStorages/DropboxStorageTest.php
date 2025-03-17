<?php

namespace Tests\Unit\Services\SongStorages;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\SongStorages\DropboxStorage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\KoelPlus\Services\TestingDropboxStorage;
use Tests\TestCase;

class DropboxStorageTest extends TestCase
{
    use TestingDropboxStorage;

    #[Test]
    public function supported(): void
    {
        $this->expectException(KoelPlusRequiredException::class);

        self::mockDropboxRefreshAccessTokenCall();

        /** @var Song $song */
        $song = Song::factory()->create();

        /** @var DropboxStorage $service */
        $service = app(DropboxStorage::class);
        $service->getSongPresignedUrl($song);
    }
}
