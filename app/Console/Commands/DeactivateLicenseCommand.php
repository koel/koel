<?php

namespace App\Console\Commands;

use App\Services\License\Contracts\LicenseServiceInterface;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class DeactivateLicenseCommand extends Command
{
    protected $signature = 'koel:license:deactivate';
    protected $description = 'Deactivate the currently active Koel Plus license';

    public function __construct(
        private readonly LicenseServiceInterface $licenseService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $status = $this->licenseService->getStatus();

        if ($status->hasNoLicense()) {
            warning('No active Plus license found.');

            return self::SUCCESS;
        }

        if (!confirm(label: 'Are you sure you want to deactivate your Koel Plus license?')) {
            warning('License deactivation aborted.');

            return self::SUCCESS;
        }

        info('Deactivating your license…');

        try {
            $this->licenseService->deactivate($status->license);
            info('Koel Plus has been deactivated. Plus features are now disabled.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            error('Failed to deactivate Koel Plus: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
