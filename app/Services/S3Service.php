<?php

namespace App\Services;

use App\Models\Song;
use Aws\S3\S3ClientInterface;
use Illuminate\Cache\Repository as Cache;

class S3Service implements ObjectStorageInterface
{
    private $s3Client;
    private $cache;

    public function __construct(?S3ClientInterface $s3Client, Cache $cache)
    {
        $this->s3Client = $s3Client;
        $this->cache = $cache;
    }

    public function getSongPublicUrl(Song $song): string
    {
        return $this->cache->remember("OSUrl/{$song->id}", 60, static function () use ($song): string {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $song->s3_params['bucket'],
                'Key' => $song->s3_params['key'],
            ]);

            // Here we specify that the request is valid for 1 hour.
            // We'll also cache the public URL for future reuse.
            $request = $this->s3Client->createPresignedRequest($cmd, '+1 hour');
            $url = (string) $request->getUri();

            return $url;
        });
    }
}
