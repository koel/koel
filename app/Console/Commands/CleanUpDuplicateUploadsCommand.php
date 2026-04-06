<?php

namespace App\Console\Commands;

use App\Models\DuplicateUpload;
use App\Services\DuplicateUploadService;
use Illuminate\Console\Command;

class CleanUpDuplicateUploadsCommand extends Command
{
    protected $signature = 'koel:clean-up-duplicate-uploads {--days=7 : Remove entries older than this many days}';
    protected $description = 'Remove stale duplicate upload entries and their associated files.';

    public function handle(DuplicateUploadService $service): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $staleUploads = DuplicateUpload::query()->where('created_at', '<', $threshold)->get();

        if ($staleUploads->isEmpty()) {
            $this->components->info('No stale duplicate uploads found.');

            return self::SUCCESS;
        }

        $userGroups = $staleUploads->groupBy('user_id');

        foreach ($userGroups as $uploads) {
            $service->discardDuplicateUploads($uploads->first()->user, $uploads->pluck('id')->all());
        }

        $this->components->info(sprintf('%d stale duplicate upload(s) removed.', $staleUploads->count()));

        return self::SUCCESS;
    }
}
