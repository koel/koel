<?php

namespace App\Providers;

use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\LocalStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\SongStorage;
use Illuminate\Support\ServiceProvider;

class SongStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SongStorage::class, function () {
            $concrete = match (config('koel.storage_driver')) {
                's3' => S3CompatibleStorage::class,
                'dropbox' => DropboxStorage::class,
                default => LocalStorage::class,
            };

            return $this->app->make($concrete);
        });

        $this->app->when(S3CompatibleStorage::class)
            ->needs('$bucket')
            ->giveConfig('filesystems.disks.s3.bucket');

        $this->app->when(DropboxStorage::class)
            ->needs('$config')
            ->giveConfig('filesystems.disks.dropbox');
    }
}
