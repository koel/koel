<?php

namespace App\Console\Commands\Storage;

use App\Services\SongStorages\DropboxStorage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

use function Laravel\Prompts\text;

class SetupDropboxStorageCommand extends SetupStorageCommand
{
    protected $signature = 'koel:storage:dropbox';
    protected $description = 'Set up Dropbox as the storage driver for Koel';

    public function handle(bool $firstTry = true): int
    {
        if (!$this->ensureKoelPlus('Dropbox')) {
            return self::FAILURE;
        }

        if ($firstTry) {
            $this->introduceSetup('Setting up Dropbox as the storage driver for Koel.');
        }

        $config = ['STORAGE_DRIVER' => 'dropbox'];

        $config['DROPBOX_APP_KEY'] = text(
            label: 'Enter your Dropbox app key',
            default: (string) config('filesystems.disks.dropbox.app_key'),
        );

        $config['DROPBOX_APP_SECRET'] = self::askForSecret(
            label: 'Enter your Dropbox app secret',
            currentValue: (string) config('filesystems.disks.dropbox.app_secret'),
        );

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

        $verified = $this->saveAndVerifyConfig(
            $config,
            static function () use ($config): void {
                config()->set('filesystems.disks.dropbox', [
                    'app_key' => $config['DROPBOX_APP_KEY'],
                    'app_secret' => $config['DROPBOX_APP_SECRET'],
                    'refresh_token' => $config['DROPBOX_REFRESH_TOKEN'],
                ]);

                Cache::forget('dropbox_access_token');

                try {
                    app()->build(DropboxStorage::class)->testSetup(); // build instead of make to avoid singleton issues
                } catch (Throwable $e) {
                    Cache::forget('dropbox_access_token');

                    throw $e;
                }
            },
            'Please make sure the app has the correct permissions and try again.',
        );

        if (!$verified) {
            return $this->handle(firstTry: false);
        }

        $this->components->info('All done!');

        return self::SUCCESS;
    }
}
