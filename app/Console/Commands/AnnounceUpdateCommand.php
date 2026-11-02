<?php

namespace App\Console\Commands;

use App\Events\NewVersionDeployed;
use App\Services\BuildIdentifier;
use Illuminate\Console\Command;

class AnnounceUpdateCommand extends Command
{
    protected $signature = 'koel:announce-update';
    protected $description = 'Let open Koel tabs know that a new version has been deployed';

    public function __construct(
        private readonly BuildIdentifier $buildIdentifier,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $build = $this->buildIdentifier->getId();

        if (!$build) {
            $this->components->warn('No front-end build found, nothing to announce.');

            return self::SUCCESS;
        }

        broadcast(new NewVersionDeployed($build));
        $this->components->info("Announced build $build.");

        return self::SUCCESS;
    }
}
