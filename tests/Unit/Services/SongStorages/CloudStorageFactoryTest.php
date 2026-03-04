<?php

namespace Tests\Unit\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\NonCloudStorageException;
use App\Services\SongStorages\CloudStorageFactory;
use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\S3LambdaStorage;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CloudStorageFactoryTest extends TestCase
{
    /** @return array<mixed> */
    public static function provideCloudStorageData(): array
    {
        return [
            'S3 Lambda' => [SongStorageType::S3_LAMBDA, S3LambdaStorage::class],
            'S3 Compatible' => [SongStorageType::S3, S3CompatibleStorage::class],
            'Dropbox' => [SongStorageType::DROPBOX, DropboxStorage::class],
        ];
    }

    #[Test]
    #[DataProvider('provideCloudStorageData')]
    public function resolve(SongStorageType $storageType, string $concreteClassName): void
    {
        Cache::set('dropbox_access_token', 'foo');
        $instance = CloudStorageFactory::make($storageType);

        self::assertInstanceOf($concreteClassName, $instance);

        Cache::delete('dropbox_access_token');
    }

    /** @return array<mixed> */
    public static function provideNonCloudStorageData(): array
    {
        return [
            'SFTP' => [SongStorageType::SFTP],
            'Local' => [SongStorageType::LOCAL],
        ];
    }

    #[Test]
    #[DataProvider('provideNonCloudStorageData')]
    public function resolveThrowsForNonCloudStorage(SongStorageType $storageType): void
    {
        $this->expectException(NonCloudStorageException::class);

        CloudStorageFactory::make($storageType);
    }
}
