<?php

namespace Tests\Unit\Services\SongStorages;

use App\Services\SongStorages\S3UploadUrlSigner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class S3UploadUrlSignerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.region' => 'us-east-1',
            'filesystems.disks.s3.bucket' => 'koel',
            'filesystems.disks.s3.endpoint' => 'https://storage.example.com',
            'filesystems.disks.s3.use_path_style_endpoint' => true,
        ]);
    }

    #[Test]
    public function signsTheDeclaredSizeIntoTheUrl(): void
    {
        $expiresAt = Carbon::now()->addHour();

        $presigned = app(S3UploadUrlSigner::class)->sign('1__random__song.mp3', 1234, $expiresAt);
        $query = Uri::of($presigned->url)->query();

        self::assertSame('https://storage.example.com/koel/1__random__song.mp3', Str::before($presigned->url, '?'));
        self::assertSame('content-length;host', $query->get('X-Amz-SignedHeaders'));
        self::assertSame(['If-None-Match' => '*'], $presigned->headers);
        self::assertSame('1__random__song.mp3', $presigned->key);
        self::assertTrue($presigned->expiresAt->eq($expiresAt));
    }

    #[Test]
    public function differentSizesGetDifferentSignatures(): void
    {
        $expiresAt = Carbon::now()->addHour();
        $signer = app(S3UploadUrlSigner::class);

        $small = Uri::of($signer->sign('1__random__song.mp3', 1234, $expiresAt)->url)->query();
        $large = Uri::of($signer->sign('1__random__song.mp3', 5678, $expiresAt)->url)->query();

        self::assertNotSame($small->get('X-Amz-Signature'), $large->get('X-Amz-Signature'));
    }
}
