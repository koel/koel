<?php

namespace App\Console\Commands;

use App\Services\License\LicenseServiceInterface;
use Illuminate\Console\Command;
use Throwable;

class ActivateLicenseCommand extends Command
{
    protected $signature = 'koel:license:activate {key : The license key to activate.}';
    protected $description = 'Activate a Koel Plus license';

    public function __construct(private LicenseServiceInterface $licenseService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('Activating license…');

        try {
            $license = $this->licenseService->activate($this->argument('key'));
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->output->success('Koel Plus activated! All Plus features are now available.');
        $this->components->twoColumnDetail('License Key', $license->short_key);

        $this->components->twoColumnDetail(
            'Registered To',
            "{$license->meta->customerName} <{$license->meta->customerEmail}>"
        );

        $this->components->twoColumnDetail('Expires On', 'Never ever');
        $this->newLine();

        return self::SUCCESS;
    }
}
