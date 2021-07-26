<?php

namespace Tests\Integration\Services;

use App\Models\Song;
use App\Services\S3Service;
use Aws\CommandInterface;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\Psr7\Request;
use Illuminate\Cache\Repository as Cache;
use Mockery;
use Tests\TestCase;

class S3ServiceTest extends TestCase
{
    private $s3Client;
    private $cache;
    private S3Service $s3Service;

    public function setUp(): void
    {
        parent::setUp();

        $this->s3Client = Mockery::mock(S3ClientInterface::class);
        $this->cache = Mockery::mock(Cache::class);
        $this->s3Service = new S3Service($this->s3Client, $this->cache);
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
