<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property array<string>|null $s3_params The bucket and key name of an S3 object.
 *
 * @method static Builder hostedOnS3()
 */
trait SupportsS3
{
    protected function s3Params(): Attribute
    {
        return Attribute::get(function (): ?array {
            if (!preg_match('/^s3:\\/\\/(.*)/', $this->path, $matches)) {
                return null;
            }

            [$bucket, $key] = explode('/', $matches[1], 2);

            return compact('bucket', 'key');
        });
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
