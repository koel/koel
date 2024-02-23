<?php

namespace App\Console\Commands;

use App\Facades\License;
use App\Services\SongStorages\DropboxStorage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\DotenvEditor;
use Throwable;

class SetupDropboxCommand extends Command
{
    protected $signature = 'koel:setup-dropbox';
    protected $description = 'Set up Dropbox as the storage driver for Koel';

    public function __construct(private Artisan $artisan, private DotenvEditor $dotenvEditor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!License::isPlus()) {
            $this->error('Dropbox as a storage driver is only available in Koel Plus.');

            return self::FAILURE;
        }

        $this->info('Setting up Dropbox as the storage driver for Koel.');

        $appKey = $this->ask('Enter your Dropbox app key');
        $appSecret = $this->ask('Enter your Dropbox app secret');

        $cacheKey = Str::uuid()->toString();

        Cache::put($cacheKey, ['app_key' => $appKey, 'app_secret' => $appSecret], now()->addMinutes(15));

        $tmpUrl = route('dropbox.authorize', ['state' => $cacheKey]);

        $this->comment('Please visit the following link to authorize Koel to access your Dropbox account:');
        $this->info($tmpUrl);
        $this->comment('The link will expire in 15 minutes.');
        $this->comment('After you have authorized Koel, please enter the access code below.');
        $accessCode = $this->ask('Enter the access code');

        $response = Http::asForm()
            ->withBasicAuth($appKey, $appSecret)
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

        $refreshToken = $response->json('refresh_token');

        $this->dotenvEditor->setKey('STORAGE_DRIVER', 'dropbox');
        $this->dotenvEditor->setKey('DROPBOX_APP_KEY', $appKey);
        $this->dotenvEditor->setKey('DROPBOX_APP_SECRET', $appSecret);
        $this->dotenvEditor->setKey('DROPBOX_REFRESH_TOKEN', $refreshToken);
        $this->dotenvEditor->setKey('DROPBOX_APP_FOLDER', $appFolder ?: '/');
        $this->dotenvEditor->save();

        $this->comment('Uploading a test file to Dropbox to ensure everything is working...');

        try {
            /** @var DropboxStorage $storage */
            $storage = app(DropboxStorage::class);
            $storage->testSetup();
        } catch (Throwable $e) {
            $this->error('Failed to upload a test file: ' . $e->getMessage() . '.');
            $this->comment('Please make sure the app has the correct permissions and try again.');

            return self::FAILURE;
        }

        $this->output->success('All done!');

        Cache::forget($cacheKey);
        $this->artisan->call('config:clear');

        return self::SUCCESS;
    }
}
