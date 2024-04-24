<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Services\SongStorages\S3CompatibleStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Jackiedo\DotenvEditor\DotenvEditor;
use Throwable;

class SetupS3StorageCommand extends Command
{
    protected $signature = 'koel:storage:s3';
    protected $description = 'Set up Amazon S3 or a compatible service as the storage driver for Koel';

    public function __construct(private readonly DotenvEditor $dotenvEditor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!License::isPlus()) {
            $this->components->error('S3 as a storage driver is only available in Koel Plus.');

            return self::FAILURE;
        }

        $this->components->info('Setting up Dropbox as the storage driver for Koel.');
        $this->components->warn('Changing the storage configuration can cause irreversible data loss.');
        $this->components->warn('Consider backing up your data before proceeding.');

        $config = ['STORAGE_DRIVER' => 's3'];

        $config['AWS_ACCESS_KEY_ID'] = $this->ask('Enter the access key ID (AWS_ACCESS_KEY_ID)');
        $config['AWS_SECRET_ACCESS_KEY'] = $this->ask('Enter the  secret access key (AWS_SECRET_ACCESS_KEY)');
        $config['AWS_REGION'] = $this->ask('Enter the region (AWS_REGION). For Cloudflare R2, use "auto".');
        $config['AWS_ENDPOINT'] = $this->ask('Enter the endpoint (AWS_ENDPOINT)');
        $config['AWS_BUCKET'] = $this->ask('Enter the bucket name (AWS_BUCKET)');

        $this->dotenvEditor->setKeys($config);
        $this->dotenvEditor->save();

        $this->comment('Uploading a test file to make sure everything is working...');

        try {
            app(S3CompatibleStorage::class)->testSetup();
        } catch (Throwable $e) {
            $this->error('Failed to upload test file: ' . $e->getMessage() . '.');
            $this->comment('Please check your configuration and try again.');

            $this->dotenvEditor->restore();
            Artisan::call('config:clear', ['--quiet' => true]);

            return self::FAILURE;
        }

        $this->components->info('All done!');

        return self::SUCCESS;
    }
}
