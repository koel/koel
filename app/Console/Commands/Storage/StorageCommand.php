<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Models\Setting;
use Illuminate\Console\Command;

class StorageCommand extends Command
{
    protected $signature = 'koel:storage';
    protected $description = 'Set up and configure Koel’s storage.';

    public function handle(): int
    {
        $this->info('This command will set up and configure Koel’s storage.');

        $this->info('Current storage configuration:');
        $this->components->twoColumnDetail('Driver', config('koel.storage_driver'));

        if (config('koel.storage_driver') === 'local') {
            $this->components->twoColumnDetail('Media path', Setting::get('media_path') ?: '<not set>');
        }

        $storageChoices = ['local' => 'This server'];

        if (License::isPlus()) {
            $storageChoices['s3'] = 'Amazon S3 or compatible services (DO Spaces, Cloudflare R2, etc.)';
            $storageChoices['dropbox'] = 'Dropbox';
        }

        $storageDriver = $this->choice('Where do you store your media files?', $storageChoices);

        if ($this->call("koel:storage:$storageDriver") === self::SUCCESS) {
            $this->output->success('Storage has been set up.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
