<?php

namespace Tests\Unit\Services;

use App\Events\LibraryChanged;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Repositories\UserRepository;
use App\Services\MediaMetadataService;
use App\Services\S3Service;
use Aws\CommandInterface;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\Psr7\Request;
use Illuminate\Cache\Repository as Cache;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;

use function Tests\create_admin;

class S3ServiceTest extends TestCase
{
    private S3ClientInterface|LegacyMockInterface|MockInterface $s3Client;
    private Cache|LegacyMockInterface|MockInterface $cache;
    private SongRepository|LegacyMockInterface|MockInterface $songRepository;
    private UserRepository|LegacyMockInterface|MockInterface $userRepository;
    private S3Service $s3Service;

    public function setUp(): void
    {
        parent::setUp();

        $this->s3Client = Mockery::mock(S3ClientInterface::class);
        $this->cache = Mockery::mock(Cache::class);

        $metadataService = Mockery::mock(MediaMetadataService::class);
        $this->songRepository = Mockery::mock(SongRepository::class);
        $this->userRepository = Mockery::mock(UserRepository::class);

        $this->s3Service = new S3Service(
            $this->s3Client,
            $this->cache,
            $metadataService,
            $this->songRepository,
            $this->userRepository
        );
    }

    public function testCreateSongEntry(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $user = create_admin();
        $this->userRepository->shouldReceive('getDefaultAdminUser')
            ->once()
            ->andReturn($user);

        $song = $this->s3Service->createSongEntry(
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

    public function testUpdateSongEntry(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $user = create_admin();
        $this->userRepository->shouldReceive('getDefaultAdminUser')
            ->once()
            ->andReturn($user);

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => 's3://foo/bar',
        ]);

        $this->s3Service->createSongEntry(
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

    public function testDeleteSong(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => 's3://foo/bar',
        ]);

        $this->songRepository->shouldReceive('getOneByPath')
            ->with('s3://foo/bar')
            ->once()
            ->andReturn($song);

        $this->s3Service->deleteSongEntry('foo', 'bar');

        self::assertModelMissing($song);
    }

    public function testGetSongPublicUrl(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => 's3://foo/bar']);

        $cmd = Mockery::mock(CommandInterface::class);

        $this->s3Client->shouldReceive('getCommand')
            ->with('GetObject', [
                'Bucket' => 'foo',
                'Key' => 'bar',
            ])
            ->andReturn($cmd);

        $request = Mockery::mock(Request::class, ['getUri' => 'https://aws.com/foo.mp3']);

        $this->s3Client->shouldReceive('createPresignedRequest')
            ->with($cmd, '+1 hour')
            ->andReturn($request);

        $this->cache->shouldReceive('remember')
            ->once()
            ->andReturn('https://aws.com/foo.mp3');

        self::assertSame('https://aws.com/foo.mp3', $this->s3Service->getSongPublicUrl($song));
    }
}
