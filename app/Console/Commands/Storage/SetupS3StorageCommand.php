<?php

namespace App\Console\Commands\Storage;

use App\Services\SongStorages\S3CompatibleStorage;

use function Laravel\Prompts\text;

class SetupS3StorageCommand extends SetupStorageCommand
{
    protected $signature = 'koel:storage:s3';
    protected $description = 'Set up Amazon S3 or a compatible service as the storage driver for Koel';

    public function handle(): int
    {
        if (!$this->ensureKoelPlus('S3')) {
            return self::FAILURE;
        }

        $this->introduceSetup('Setting up S3 or an S3-compatible service as the storage driver for Koel.');

        $config = ['STORAGE_DRIVER' => 's3'];
        $config['AWS_ACCESS_KEY_ID'] = text(label: 'Enter the access key ID', hint: 'AWS_ACCESS_KEY_ID');
        $config['AWS_SECRET_ACCESS_KEY'] = self::askForSecret(
            label: 'Enter the secret access key',
            currentValue: (string) config('filesystems.disks.s3.secret'),
            hint: 'AWS_SECRET_ACCESS_KEY.',
        );
        $config['AWS_REGION'] = text(label: 'Enter the region', hint: 'AWS_REGION. For Cloudflare R2, use "auto".');
        $config['AWS_ENDPOINT'] = text(label: 'Enter the endpoint', hint: 'AWS_ENDPOINT');
        $config['AWS_BUCKET'] = text(label: 'Enter the bucket name', hint: 'AWS_BUCKET');

        $verified = $this->saveAndVerifyConfig(
            $config,
            static function () use ($config): void {
                config()->set('filesystems.disks.s3.key', $config['AWS_ACCESS_KEY_ID']);
                config()->set('filesystems.disks.s3.secret', $config['AWS_SECRET_ACCESS_KEY']);
                config()->set('filesystems.disks.s3.region', $config['AWS_REGION']);
                config()->set('filesystems.disks.s3.endpoint', $config['AWS_ENDPOINT']);
                config()->set('filesystems.disks.s3.bucket', $config['AWS_BUCKET']);

                app()->build(S3CompatibleStorage::class)->testSetup();
            },
            'Please check your configuration and try again.',
        );

        if (!$verified) {
            return self::FAILURE;
        }

        $this->components->info('All done!');

        return self::SUCCESS;
    }
}
