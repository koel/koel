<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * @property array<string> $s3_params
 *
 * @method static Builder hostedOnS3()
 */
trait SupportsS3
{
    /**
     * Get the bucket and key name of an S3 object.
     *
     * @return array<string>|null
     */
    public function getS3ParamsAttribute(): ?array
    {
        if (!preg_match('/^s3:\\/\\/(.*)/', $this->path, $matches)) {
            return null;
        }

        [$bucket, $key] = explode('/', $matches[1], 2);

        return compact('bucket', 'key');
    }

    public static function getPathFromS3BucketAndKey(string $bucket, string $key): string
    {
        return "s3://$bucket/$key";
    }

    public function scopeHostedOnS3(Builder $query): Builder
    {
        return $query->where('path', 'LIKE', 's3://%');
    }
}
