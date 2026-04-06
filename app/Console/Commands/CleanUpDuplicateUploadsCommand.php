<?php

namespace App\Console\Commands;

use App\Repositories\DuplicateUploadRepository;
use App\Services\DuplicateUploadService;
use Illuminate\Console\Command;

class CleanUpDuplicateUploadsCommand extends Command
{
    protected $signature = 'koel:clean-up-duplicate-uploads {--days=7 : Remove entries older than this many days}';
    protected $description = 'Remove stale duplicate upload entries and their associated files.';

    public function __construct(
        private readonly DuplicateUploadService $service,
        private readonly DuplicateUploadRepository $repository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->components->error('The --days option must be a positive integer.');

            return self::FAILURE;
        }

        $staleUploads = $this->repository->getStaleUploads($days);

        if ($staleUploads->isEmpty()) {
            $this->components->info('No stale duplicate uploads found.');

            return self::SUCCESS;
        }

        $this->service->discard($staleUploads);

        $this->components->info(sprintf('%d stale duplicate upload(s) removed.', $staleUploads->count()));

        return self::SUCCESS;
    }
}
