<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Services\DotenvEditor;
use App\Services\SongStorages\WebDAVStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class SetupWebDAVStorageCommand extends Command
{
    protected $signature = 'koel:storage:webdav';
    protected $description = 'Set up WebDAV (NextCloud, ownCloud, etc.) as the storage driver for Koel';

    public function __construct(
        private readonly DotenvEditor $dotenvEditor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!License::isPlus()) {
            $this->components->error('WebDAV as a storage driver is only available in Koel Plus.');

            return self::FAILURE;
        }

        $this->components->info('Setting up WebDAV as the storage driver for Koel.');
        $this->components->warn('Changing the storage configuration can cause irreversible data loss.');
        $this->components->warn('Consider backing up your data before proceeding.');

        $config = ['STORAGE_DRIVER' => 'webdav'];

        $config['WEBDAV_BASE_URL'] = Str::finish(
            trim(text(
                label: 'Enter your WebDAV base URL',
                default: (string) env('WEBDAV_BASE_URL'),
                hint: 'For NextCloud, this looks like https://your-nextcloud.example/remote.php/dav/files/<username>/',
            )),
            '/',
        );

        $config['WEBDAV_USERNAME'] = text(
            label: 'Enter your WebDAV username',
            default: (string) env('WEBDAV_USERNAME'),
        );

        $existingPassword = (string) env('WEBDAV_PASSWORD');

        $enteredPassword = password(
            label: 'Enter your WebDAV password',
            hint: $existingPassword === '' ? '' : 'Leave blank to keep the current password.',
        );

        $config['WEBDAV_PASSWORD'] = $enteredPassword !== '' ? $enteredPassword : $existingPassword;

        $config['WEBDAV_PATH_PREFIX'] = trim(
            text(
                label: 'Optional path prefix beneath the base URL',
                default: (string) env('WEBDAV_PATH_PREFIX'),
                hint: 'No leading or trailing slash. Leave empty to use the root.',
            ),
            '/',
        );

        $this->dotenvEditor->backup()->setKeys($config);

        config()->set('filesystems.disks.webdav', [
            'driver' => 'webdav',
            'baseUri' => $config['WEBDAV_BASE_URL'],
            'userName' => $config['WEBDAV_USERNAME'],
            'password' => $config['WEBDAV_PASSWORD'],
            'pathPrefix' => $config['WEBDAV_PATH_PREFIX'],
        ]);

        $this->comment('Uploading a test file to make sure everything is working...');

        try {
            app()->build(WebDAVStorage::class)->testSetup();
        } catch (Throwable $e) {
            $this->error('Failed to connect to the WebDAV server: ' . $e->getMessage() . '.');
            $this->comment('Please check your configuration and run this command again.');

            $this->dotenvEditor->restore();
            Artisan::call('config:clear', ['--quiet' => true]);

            return self::FAILURE;
        }

        $this->components->info('All done!');

        return self::SUCCESS;
    }
}
