<?php

namespace App\Console\Commands;

use App\Services\License\LicenseServiceInterface;
use Illuminate\Console\Command;
use Throwable;

class DeactivateLicenseCommand extends Command
{
    protected $signature = 'koel:license:deactivate';
    protected $description = 'Deactivate the currently active Koel Plus license';

    public function __construct(private LicenseServiceInterface $plusService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $status = $this->plusService->getStatus(checkCache: false);

        if ($status->hasNoLicense()) {
            $this->components->warn('No active Plus license found.');

            return self::SUCCESS;
        }

        if (!$this->confirm('Are you sure you want to deactivate your Koel Plus license?')) {
            $this->output->warning('License deactivation aborted.');

            return self::SUCCESS;
        }

        $this->components->info('Deactivating your license…');

        try {
            $this->plusService->deactivate($status->license);
            $this->components->info('Koel Plus has been deactivated. Plus features are now disabled.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->components->error('Failed to deactivate Koel Plus: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
