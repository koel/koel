<?php

namespace App\Providers;

use AWS;
use Aws\AwsClientInterface;
use Aws\S3\S3ClientInterface;
use Illuminate\Support\ServiceProvider;

class ObjectStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(S3ClientInterface::class, static function (): AwsClientInterface {
            return AWS::createClient('s3');
        });
    }
}
