<?php

namespace App\Providers;

use Aws\AwsClientInterface;
use Aws\S3\S3ClientInterface;
use Illuminate\Support\ServiceProvider;

class ObjectStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(S3ClientInterface::class, static function (): ?AwsClientInterface {
            // If these two values are not configured in .env, AWS will attempt initializing
            // the client with null values and throw an error.
            if (!config('aws.credentials.key') || !config('aws.credentials.secret')) {
                return null;
            }

            return app('aws')->createClient('s3');
        });
    }
}
