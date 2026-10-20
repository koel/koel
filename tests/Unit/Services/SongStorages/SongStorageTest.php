<?php

namespace Tests\Unit\Services\SongStorages;

use App\Services\SongStorages\LocalStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongStorageTest extends TestCase
{
    /** @return array<string, array<string>> */
    public static function provideLocations(): array
    {
        return [
            'Cloud key' => ['s3://koel/01abc__01xyz__My Song.mp3', 'My Song.mp3'],
            'Remote path' => ['sftp://01abc__01xyz__My__Song.mp3', 'My__Song.mp3'],
            'Local path' => ['/media/__KOEL_UPLOADS_$1__/My Song.mp3', 'My Song.mp3'],
            'Name with a single separator' => ['/media/My__Song.mp3', 'My__Song.mp3'],
        ];
    }

    #[Test]
    #[DataProvider('provideLocations')]
    public function getOriginalFileName(string $location, string $originalFileName): void
    {
        self::assertSame($originalFileName, app(LocalStorage::class)->getOriginalFileName($location));
    }
}
