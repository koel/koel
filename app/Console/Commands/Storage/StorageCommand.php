<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Models\Setting;
use Illuminate\Console\Command;

class StorageCommand extends Command
{
    protected $signature = 'koel:storage';
    protected $description = 'Set up and configure Koel’s storage';

    public function handle(): int
    {
        $this->info('This command will set up and configure Koel’s storage.');

        $this->info('Current storage configuration:');
        $this->components->twoColumnDetail('Driver', config('koel.storage_driver'));

        if (config('koel.storage_driver') === 'local') {
            $this->components->twoColumnDetail('Media path', Setting::get('media_path') ?: '<not set>');
        }

        if (License::isPlus()) {
            $choices = [
                'local' => 'This server',
                's3' => 'Amazon S3 or compatible services (DO Spaces, Cloudflare R2, etc.)',
                'dropbox' => 'Dropbox',
            ];

            $driver = $this->choice(
                'Where do you want to store your media files?',
                $choices,
                config('koel.storage_driver')
            );
        } else {
            $driver = 'local';
        }

        if ($this->call("koel:storage:$driver") === self::SUCCESS) {
            $this->output->success('Storage has been set up.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
