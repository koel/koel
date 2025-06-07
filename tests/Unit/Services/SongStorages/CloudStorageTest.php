<?php

namespace Tests\Unit\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\S3LambdaStorage;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CloudStorageTest extends TestCase
{
    /** @return array<mixed> */
    public static function provideConcreteInstances(): array
    {
        return [
            'S3 Lambda' => [SongStorageType::S3_LAMBDA, S3LambdaStorage::class],
            'S3 Compatible' => [SongStorageType::S3, S3CompatibleStorage::class],
            'Dropbox' => [SongStorageType::DROPBOX, DropboxStorage::class],
            'Local' => [SongStorageType::LOCAL, null],
            'SFTP' => [SongStorageType::SFTP, null],
        ];
    }

    #[Test]
    #[DataProvider('provideConcreteInstances')]
    public function resolve(SongStorageType $storageType, ?string $concreteClassName): void
    {
        Cache::set('dropbox_access_token', 'foo');

        /** @var Song $song */
        $song = Song::factory(['storage' => $storageType])->create();

        $instance = CloudStorage::resolve($song);

        if ($concreteClassName) {
            $this->assertInstanceOf($concreteClassName, $instance);
        } else {
            $this->assertNull($instance);
        }

        Cache::delete('dropbox_access_token');
    }
}
