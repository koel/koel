<?php

namespace App\Services\SongStorages;

use App\Values\PresignedUpload;
use Aws\S3\S3Client;
use Aws\Signature\S3SignatureV4;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

use function Aws\serialize;

class S3UploadUrlSigner
{
    public function sign(string $key, int $size, Carbon $expiresAt): PresignedUpload
    {
        /** @var AwsS3V3Adapter $disk */
        $disk = Storage::disk('s3');
        $client = $disk->getClient();

        $command = $client->getCommand('PutObject', [
            'Bucket' => $disk->getConfig()['bucket'],
            'Key' => $key,
            'IfNoneMatch' => '*',
        ]);

        $command->getHandlerList()->remove('signer');
        $command->getHandlerList()->remove('s3.checksum');

        $request = serialize($command)->withHeader('Content-Length', (string) $size);

        $signedRequest = self::makeSizeAndNoOverwriteBindingSigner($client)
            ->presign($request, $client->getCredentials()->wait(), $expiresAt);

        return PresignedUpload::make(
            key: $key,
            url: (string) $signedRequest->getUri(),
            headers: ['If-None-Match' => '*'],
            expiresAt: $expiresAt,
        );
    }

    private static function makeSizeAndNoOverwriteBindingSigner(S3Client $client): S3SignatureV4
    {
        return new class('s3', $client->getConfig('signing_region')) extends S3SignatureV4 {
            /** @return array<string, true> */
            protected function getHeaderBlacklist(): array
            {
                $blacklist = parent::getHeaderBlacklist();
                unset($blacklist['content-length'], $blacklist['if-none-match']);

                return $blacklist;
            }
        };
    }
}
