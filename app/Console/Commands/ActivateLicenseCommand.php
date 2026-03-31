<?php

namespace App\Console\Commands;

use App\Services\License\Contracts\LicenseServiceInterface;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ActivateLicenseCommand extends Command
{
    protected $signature = 'koel:license:activate {key : The license key to activate.}';
    protected $description = 'Activate a Koel Plus license';

    public function __construct(
        private readonly LicenseServiceInterface $licenseService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        info('Activating license…');

        try {
            $license = $this->licenseService->activate($this->argument('key'));
        } catch (Throwable $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        info('Koel Plus activated! All Plus features are now available.');
        $this->components->twoColumnDetail('License Key', $license->short_key);

        $this->components->twoColumnDetail(
            'Registered To',
            "{$license->meta->customerName} <{$license->meta->customerEmail}>",
        );

        $this->components->twoColumnDetail('Expires On', 'Never ever');
        $this->newLine();

        return self::SUCCESS;
    }
}
