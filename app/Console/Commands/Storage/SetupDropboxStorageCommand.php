<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Services\SongStorages\DropboxStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Jackiedo\DotenvEditor\DotenvEditor;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class SetupDropboxStorageCommand extends Command
{
    protected $signature = 'koel:storage:dropbox';
    protected $description = 'Set up Dropbox as the storage driver for Koel';

    public function __construct(
        private readonly DotenvEditor $dotenvEditor,
    ) {
        parent::__construct();
    }

    public function handle(bool $firstTry = true): int
    {
        if (!License::isPlus()) {
            error('Dropbox as a storage driver is only available in Koel Plus.');

            return self::FAILURE;
        }

        if ($firstTry) {
            info('Setting up Dropbox as the storage driver for Koel.');
            warning('Changing the storage configuration can cause irreversible data loss.');
            warning('Consider backing up your data before proceeding.');
        }

        $config = ['STORAGE_DRIVER' => 'dropbox'];
        $config['DROPBOX_APP_KEY'] = text(label: 'Enter your Dropbox app key', default: env('DROPBOX_APP_KEY') ?? '');
        $config['DROPBOX_APP_SECRET'] = password(label: 'Enter your Dropbox app secret');

        info('Visit the following link to authorize Koel to access your Dropbox account.');
        info('After you have authorized Koel, enter the access code below.');
        info(route('dropbox.authorize', ['key' => $config['DROPBOX_APP_KEY']]));

        $accessCode = text(label: 'Access code');

        $response = Http::asForm()
            ->withBasicAuth($config['DROPBOX_APP_KEY'], $config['DROPBOX_APP_SECRET'])
            ->post('https://api.dropboxapi.com/oauth2/token', [
                'code' => $accessCode,
                'grant_type' => 'authorization_code',
            ]);

        if ($response->failed()) {
            error('Failed to authorize with Dropbox. The server said: ' . $response->json('error_description') . '.');

            info('Please try again.');

            return $this->handle(firstTry: false);
        }

        $config['DROPBOX_REFRESH_TOKEN'] = $response->json('refresh_token');

        $this->dotenvEditor->setKeys($config);
        $this->dotenvEditor->save();

        config()->set('filesystems.disks.dropbox', [
            'app_key' => $config['DROPBOX_APP_KEY'],
            'app_secret' => $config['DROPBOX_APP_SECRET'],
            'refresh_token' => $config['DROPBOX_REFRESH_TOKEN'],
        ]);

        info('Uploading a test file to make sure everything is working...');

        try {
            /** @var DropboxStorage $storage */
            $storage = app()->build(DropboxStorage::class); // build instead of make to avoid singleton issues
            $storage->testSetup();
        } catch (Throwable $e) {
            error('Failed to upload test file: ' . $e->getMessage() . '.');
            info('Please make sure the app has the correct permissions and try again.');

            $this->dotenvEditor->restore();
            Artisan::call('config:clear', ['--quiet' => true]);

            return $this->handle(firstTry: false);
        }

        info('All done!');

        return self::SUCCESS;
    }
}
