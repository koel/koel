<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Services\DotenvEditor;
use App\Services\SongStorages\DropboxStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Throwable;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

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
            $this->components->error('Dropbox as a storage driver is only available in Koel Plus.');

            return self::FAILURE;
        }

        if ($firstTry) {
            $this->components->info('Setting up Dropbox as the storage driver for Koel.');
            $this->components->warn('Changing the storage configuration can cause irreversible data loss.');
            $this->components->warn('Consider backing up your data before proceeding.');
        }

        $config = ['STORAGE_DRIVER' => 'dropbox'];

        $config['DROPBOX_APP_KEY'] = text(
            label: 'Enter your Dropbox app key',
            default: (string) env('DROPBOX_APP_KEY'),
        );

        $config['DROPBOX_APP_SECRET'] = password(
            label: 'Enter your Dropbox app secret',
            hint: 'Leave blank to keep the current secret.',
        );
        $config['DROPBOX_APP_SECRET'] = $config['DROPBOX_APP_SECRET'] !== ''
            ? $config['DROPBOX_APP_SECRET']
            : (string) env('DROPBOX_APP_SECRET');

        $accessCode = text(
            label: 'Access code',
            hint: 'Visit '
            . route('dropbox.authorize', ['key' => $config['DROPBOX_APP_KEY']])
            . ' to authorize Koel, then paste the access code here.',
        );

        $response = Http::asForm()
            ->withBasicAuth($config['DROPBOX_APP_KEY'], $config['DROPBOX_APP_SECRET'])
            ->post('https://api.dropboxapi.com/oauth2/token', [
                'code' => $accessCode,
                'grant_type' => 'authorization_code',
            ]);

        if ($response->failed()) {
            $this->error(
                'Failed to authorize with Dropbox. The server said: ' . $response->json('error_description') . '.',
            );

            $this->info('Please try again.');

            return $this->handle(firstTry: false);
        }

        $config['DROPBOX_REFRESH_TOKEN'] = $response->json('refresh_token');

        $this->dotenvEditor->backup()->setKeys($config);

        config()->set('filesystems.disks.dropbox', [
            'app_key' => $config['DROPBOX_APP_KEY'],
            'app_secret' => $config['DROPBOX_APP_SECRET'],
            'refresh_token' => $config['DROPBOX_REFRESH_TOKEN'],
        ]);

        $this->comment('Uploading a test file to make sure everything is working...');

        try {
            app()->build(DropboxStorage::class)->testSetup(); // build instead of make to avoid singleton issues
        } catch (Throwable $e) {
            $this->error('Failed to upload test file: ' . $e->getMessage() . '.');
            $this->comment('Please make sure the app has the correct permissions and try again.');

            $this->dotenvEditor->restore();
            Artisan::call('config:clear', ['--quiet' => true]);

            return $this->handle(firstTry: false);
        }

        $this->components->info('All done!');

        return self::SUCCESS;
    }
}
