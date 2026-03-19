<?php

namespace Tests\Unit\Models;

use App\Enums\SongStorageType;
use App\Models\Genre;
use App\Models\Song;
use App\Values\SongStorageMetadata\S3CompatibleMetadata;
use App\Values\SongStorageMetadata\S3LambdaMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongTest extends TestCase
{
    #[Test]
    public function retrievedLyricsPreserveTimestamps(): void
    {
        $song = Song::factory()->createOne(['lyrics' => "[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3"]);

        self::assertSame("[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3", $song->lyrics);
        self::assertSame("[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3", $song->getAttributes()['lyrics']);
    }

    #[Test]
    public function syncGenres(): void
    {
        $song = Song::factory()->createOne();
        $song->syncGenres('Pop, Rock');

        self::assertCount(2, $song->genres);
        self::assertEqualsCanonicalizing(['Pop', 'Rock'], $song->genres->pluck('name')->all());
    }

    /** @return array<mixed> */
    public static function provideGenreData(): array
    {
        return [
            ['Rock, Pop',    true],
            ['Pop, Rock',    true],
            ['Rock,   Pop ', true],
            ['Rock',         false],
            ['Jazz, Pop',    false],
        ];
    }

    #[Test]
    #[DataProvider('provideGenreData')]
    public function genreEqualsTo(string $target, bool $isEqual): void
    {
        $song = Song::factory()
            ->hasAttached(Genre::factory()->createOne(['name' => 'Pop']))
            ->hasAttached(Genre::factory()->createOne(['name' => 'Rock']))
            ->create()
            ->refresh();

        self::assertSame($isEqual, $song->genreEqualsTo($target));
    }

    #[Test]
    public function s3StorageMetadataHandlesNestedKeys(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://my-bucket/path/to/nested/file.mp3',
            'storage' => SongStorageType::S3,
        ]);

        $metadata = $song->storage_metadata;

        self::assertInstanceOf(S3CompatibleMetadata::class, $metadata);
        self::assertSame('my-bucket', $metadata->bucket);
        self::assertSame('path/to/nested/file.mp3', $metadata->key);
    }

    #[Test]
    public function s3LambdaStorageMetadataHandlesNestedKeys(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://my-bucket/path/to/nested/file.mp3',
            'storage' => SongStorageType::S3_LAMBDA,
        ]);

        $metadata = $song->storage_metadata;

        self::assertInstanceOf(S3LambdaMetadata::class, $metadata);
        self::assertSame('my-bucket', $metadata->bucket);
        self::assertSame('path/to/nested/file.mp3', $metadata->key);
    }

    #[Test]
    public function deletingByChunk(): void
    {
        Song::factory()->createMany(5);

        Song::deleteByChunk(Song::query()->get()->modelKeys(), 1);

        self::assertSame(0, Song::query()->count());
    }
}
