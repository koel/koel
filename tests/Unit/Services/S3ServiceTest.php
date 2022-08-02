<?php

namespace Tests\Unit\Services;

use App\Models\Song;
use App\Repositories\SongRepository;
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

class S3ServiceTest extends TestCase
{
    private S3ClientInterface|LegacyMockInterface|MockInterface $s3Client;
    private Cache|LegacyMockInterface|MockInterface $cache;
    private S3Service $s3Service;

    public function setUp(): void
    {
        parent::setUp();

        $this->s3Client = Mockery::mock(S3ClientInterface::class);
        $this->cache = Mockery::mock(Cache::class);

        $metadataService = Mockery::mock(MediaMetadataService::class);
        $songRepository = Mockery::mock(SongRepository::class);

        $this->s3Service = new S3Service($this->s3Client, $this->cache, $metadataService, $songRepository);
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
