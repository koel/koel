<?php

namespace App\Console\Commands\Storage;

use App\Services\SongStorages\WebDAVStorage;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class SetupWebDAVStorageCommand extends SetupStorageCommand
{
    protected $signature = 'koel:storage:webdav';
    protected $description = 'Set up WebDAV (NextCloud, ownCloud, etc.) as the storage driver for Koel';

    public function handle(): int
    {
        if (!$this->ensureKoelPlus('WebDAV')) {
            return self::FAILURE;
        }

        $this->introduceSetup('Setting up WebDAV as the storage driver for Koel.');

        $config = ['STORAGE_DRIVER' => 'webdav'];

        $config['WEBDAV_BASE_URL'] = Str::finish(
            trim(text(
                label: 'Enter your WebDAV base URL',
                default: rtrim((string) config('filesystems.disks.webdav.baseUri'), '/'),
                hint: 'For NextCloud, this looks like https://your-nextcloud.example/remote.php/dav/files/<username>/',
            )),
            '/',
        );

        $config['WEBDAV_USERNAME'] = text(
            label: 'Enter your WebDAV username',
            default: (string) config('filesystems.disks.webdav.userName'),
        );

        $config['WEBDAV_PASSWORD'] = self::askForSecret(
            label: 'Enter your WebDAV password',
            currentValue: (string) config('filesystems.disks.webdav.password'),
        );

        $config['WEBDAV_PATH_PREFIX'] = trim(
            text(
                label: 'Optional path prefix beneath the base URL',
                default: (string) config('filesystems.disks.webdav.pathPrefix'),
                hint: 'No leading or trailing slash. Leave empty to use the root.',
            ),
            '/',
        );

        $verified = $this->saveAndVerifyConfig(
            $config,
            static function () use ($config): void {
                config()->set('filesystems.disks.webdav', [
                    'driver' => 'webdav',
                    'baseUri' => $config['WEBDAV_BASE_URL'],
                    'userName' => $config['WEBDAV_USERNAME'],
                    'password' => $config['WEBDAV_PASSWORD'],
                    'pathPrefix' => $config['WEBDAV_PATH_PREFIX'],
                ]);

                app()->build(WebDAVStorage::class)->testSetup();
            },
            'Please check your configuration and run this command again.',
        );

        if (!$verified) {
            return self::FAILURE;
        }

        $this->components->info('All done!');

        return self::SUCCESS;
    }
}
