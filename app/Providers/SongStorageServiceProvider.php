<?php

namespace App\Providers;

use App\Services\SongStorages\DropboxStorage;
use App\Services\SongStorages\LocalStorage;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\SftpStorage;
use App\Services\SongStorages\SongStorage;
use App\Services\SongStorages\WebDAVStorage;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client as WebDAVClient;

class SongStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SongStorage::class, function () {
            $concrete = match (config('koel.storage_driver')) {
                's3' => S3CompatibleStorage::class,
                'dropbox' => DropboxStorage::class,
                'sftp' => SftpStorage::class,
                'webdav' => WebDAVStorage::class,
                default => LocalStorage::class,
            };

            return $this->app->make($concrete);
        });
    }

    public function boot(): void
    {
        // @mago-ignore lint:prefer-static-closure
        Storage::extend('webdav', function (Container $app, array $config): FilesystemAdapter {
            $client = new WebDAVClient([
                'baseUri' => (string) ($config['baseUri'] ?? ''),
                'userName' => (string) ($config['userName'] ?? ''),
                'password' => (string) ($config['password'] ?? ''),
            ]);

            $adapter = new WebDAVAdapter($client, (string) ($config['pathPrefix'] ?? ''));

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }
}
