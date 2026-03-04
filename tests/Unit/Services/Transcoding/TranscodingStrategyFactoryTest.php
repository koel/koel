<?php

namespace Tests\Unit\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Services\Transcoding\CloudTranscodingStrategy;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Services\Transcoding\SftpTranscodingStrategy;
use App\Services\Transcoding\TranscodeStrategyFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranscodingStrategyFactoryTest extends TestCase
{
    /** @return array<mixed> */
    public static function provideMakeData(): array
    {
        return [
            'Local' => [SongStorageType::LOCAL, LocalTranscodingStrategy::class],
            'S3' => [SongStorageType::S3, CloudTranscodingStrategy::class],
            'S3 Lambda' => [SongStorageType::S3_LAMBDA, CloudTranscodingStrategy::class],
            'Dropbox' => [SongStorageType::DROPBOX, CloudTranscodingStrategy::class],
            'SFTP' => [SongStorageType::SFTP, SftpTranscodingStrategy::class],
        ];
    }

    #[Test]
    #[DataProvider('provideMakeData')]
    public function make(SongStorageType $storageType, string $concreteClass): void
    {
        self::assertInstanceOf($concreteClass, TranscodeStrategyFactory::make($storageType));
    }
}
