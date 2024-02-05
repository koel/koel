<?php

namespace App\Providers;

use App\Services\SongStorage\DropboxStorage;
use App\Services\SongStorage\LocalStorage;
use App\Services\SongStorage\S3CompatibleStorage;
use App\Services\SongStorage\SongStorage;
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
            ->needs('$token')
            ->giveConfig('filesystems.disks.dropbox.token');

        $this->app->when(DropboxStorage::class)
            ->needs('$folder')
            ->giveConfig('filesystems.disks.dropbox.folder');
    }
}
