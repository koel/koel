<?php

namespace Tests\Unit\Services\SongStorages;

use App\Models\Song;
use App\Repositories\SongRepository;
use App\Repositories\UserRepository;
use App\Services\MediaMetadataService;
use App\Services\SongStorages\S3LambdaStorage;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class S3LambdaStorageTest extends TestCase
{
    private SongRepository|LegacyMockInterface|MockInterface $songRepository;
    private UserRepository|LegacyMockInterface|MockInterface $userRepository;
    private S3LambdaStorage $storage;

    public function setUp(): void
    {
        parent::setUp();

        $metadataService = Mockery::mock(MediaMetadataService::class);
        $this->songRepository = Mockery::mock(SongRepository::class);
        $this->userRepository = Mockery::mock(UserRepository::class);

        $this->storage = new S3LambdaStorage(
            $metadataService,
            $this->songRepository,
            $this->userRepository
        );
    }

    #[Test]
    public function createSongEntry(): void
    {
        $user = create_admin();
        $this->userRepository->shouldReceive('getDefaultAdminUser')
            ->once()
            ->andReturn($user);

        $song = $this->storage->createSongEntry(
            bucket: 'foo',
            key: 'bar',
            artistName: 'Queen',
            albumName: 'A Night at the Opera',
            albumArtistName: 'Queen',
            cover: [],
            title: 'Bohemian Rhapsody',
            duration: 355.5,
            track: 1,
            lyrics: 'Is this the real life?'
        );

        self::assertSame('Queen', $song->artist->name);
        self::assertSame('A Night at the Opera', $song->album->name);
        self::assertSame('Queen', $song->album_artist->name);
        self::assertSame('Bohemian Rhapsody', $song->title);
        self::assertSame(355.5, $song->length);
        self::assertSame('Is this the real life?', $song->lyrics);
        self::assertSame(1, $song->track);
        self::assertSame($user->id, $song->owner_id);
    }

    #[Test]
    public function updateSongEntry(): void
    {
        $user = create_admin();

        $this->userRepository->shouldReceive('getDefaultAdminUser')
            ->once()
            ->andReturn($user);

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => 's3://foo/bar',
            'storage' => 's3-lambda',
        ]);

        $this->storage->createSongEntry(
            bucket: 'foo',
            key: 'bar',
            artistName: 'Queen',
            albumName: 'A Night at the Opera',
            albumArtistName: 'Queen',
            cover: [],
            title: 'Bohemian Rhapsody',
            duration: 355.5,
            track: 1,
            lyrics: 'Is this the real life?'
        );

        self::assertSame(1, Song::query()->count());

        $song->refresh();

        self::assertSame('Queen', $song->artist->name);
        self::assertSame('A Night at the Opera', $song->album->name);
        self::assertSame('Queen', $song->album_artist->name);
        self::assertSame('Bohemian Rhapsody', $song->title);
        self::assertSame(355.5, $song->length);
        self::assertSame('Is this the real life?', $song->lyrics);
        self::assertSame(1, $song->track);
        self::assertSame($user->id, $song->owner_id);
    }

    #[Test]
    public function deleteSong(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => 's3://foo/bar',
            'storage' => 's3-lambda',
        ]);

        $this->songRepository->shouldReceive('findOneByPath')
            ->with('s3://foo/bar')
            ->once()
            ->andReturn($song);

        $this->storage->deleteSongEntry('foo', 'bar');

        self::assertModelMissing($song);
    }
}
