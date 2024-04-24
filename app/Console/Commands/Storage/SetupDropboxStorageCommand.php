<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Services\SongStorages\DropboxStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\DotenvEditor;
use Throwable;

class SetupDropboxStorageCommand extends Command
{
    protected $signature = 'koel:storage:dropbox';
    protected $description = 'Set up Dropbox as the storage driver for Koel';

    public function __construct(private readonly DotenvEditor $dotenvEditor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!License::isPlus()) {
            $this->components->error('Dropbox as a storage driver is only available in Koel Plus.');

            return self::FAILURE;
        }

        $this->components->info('Setting up Dropbox as the storage driver for Koel.');
        $this->components->warn('Changing the storage configuration can cause irreversible data loss.');
        $this->components->warn('Consider backing up your data before proceeding.');

        $config = ['STORAGE_DRIVER' => 'dropbox'];
        $config['DROPBOX_APP_KEY'] = $this->ask('Enter your Dropbox app key');
        $config['DROPBOX_APP_SECRET'] = $this->ask('Enter your Dropbox app secret');

        $cacheKey = Str::uuid()->toString();

        Cache::put(
            $cacheKey,
            ['app_key' => $config['DROPBOX_APP_KEY'], 'app_secret' => $config['DROPBOX_APP_SECRET']],
            now()->addMinutes(15)
        );

        $tmpUrl = route('dropbox.authorize', ['state' => $cacheKey]);

        $this->comment('Please visit the following link to authorize Koel to access your Dropbox account:');
        $this->info($tmpUrl);
        $this->comment('The link will expire in 15 minutes.');
        $this->comment('After you have authorized Koel, enter the access code below.');
        $accessCode = $this->ask('Enter the access code');

        $response = Http::asForm()
            ->withBasicAuth($config['DROPBOX_APP_KEY'], $config['DROPBOX_APP_SECRET'])
            ->post('https://api.dropboxapi.com/oauth2/token', [
                'code' => $accessCode,
                'grant_type' => 'authorization_code',
            ]);

        if ($response->failed()) {
            $this->error(
                'Failed to authorize with Dropbox. The server said: ' . $response->json('error_description') . '.'
            );

            return self::FAILURE;
        }

        $config['DROPBOX_REFRESH_TOKEN'] = $response->json('refresh_token');

        $this->dotenvEditor->setKeys($config);
        $this->dotenvEditor->save();
        Artisan::call('config:clear', ['--quiet' => true]);

        $this->comment('Uploading a test file to make sure everything is working...');

        try {
            app(DropboxStorage::class)->testSetup();
        } catch (Throwable $e) {
            $this->error('Failed to upload test file: ' . $e->getMessage() . '.');
            $this->comment('Please make sure the app has the correct permissions and try again.');

            $this->dotenvEditor->restore();
            Artisan::call('config:clear', ['--quiet' => true]);

            return self::FAILURE;
        }

        $this->components->info('All done!');

        Cache::forget($cacheKey);

        return self::SUCCESS;
    }
}
