<?php

namespace Tests\Unit\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\LocalStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\S3LambdaStorage;
use App\Services\SongStorages\SftpStorage;
use App\Services\SongStorages\SongStorage;
use App\Services\SongStorages\SongStorageFactory;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongStorageFactoryTest extends TestCase
{
    /** @return array<mixed> */
    public static function provideStorageData(): array
    {
        return [
            'Local' => [SongStorageType::LOCAL, LocalStorage::class],
            'SFTP' => [SongStorageType::SFTP, SftpStorage::class],
            'S3 Compatible' => [SongStorageType::S3, S3CompatibleStorage::class],
            'S3 Lambda' => [SongStorageType::S3_LAMBDA, S3LambdaStorage::class],
            'Dropbox' => [SongStorageType::DROPBOX, DropboxStorage::class],
        ];
    }

    #[Test]
    #[DataProvider('provideStorageData')]
    public function resolve(SongStorageType $storageType, string $concreteClassName): void
    {
        Cache::set('dropbox_access_token', 'foo');
        $instance = SongStorageFactory::make($storageType);

        self::assertInstanceOf($concreteClassName, $instance);
        self::assertInstanceOf(SongStorage::class, $instance);

        Cache::delete('dropbox_access_token');
    }
}
