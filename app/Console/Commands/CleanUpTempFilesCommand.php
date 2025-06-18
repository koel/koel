<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanUpTempFilesCommand extends Command
{
    protected $signature = 'koel:clean-up-temp-files {--age=1440 : The age of temporary files to remove in minutes}';
    protected $description = 'Remove temporary files older than a certain age';

    public function handle(): int
    {
        $maxAgeMinutes = (int) $this->option('age');
        $dir = artifact_path('tmp');

        $files = File::allFiles($dir);
        $count = 0;

        foreach ($files as $file) {
            if (abs(now()->diffInMinutes(Carbon::createFromTimestamp($file->getMTime()))) > $maxAgeMinutes) {
                File::delete($file->getPathname());
                $this->components->info("Deleted {$file->getPathname()}");
                $count++;
            }
        }

        if ($count === 0) {
            $this->components->info("No temporary files older than $maxAgeMinutes minutes to delete.");
        } else {
            $this->components->info("Deleted {$count} temporary files older than {$maxAgeMinutes} minutes.");
        }

        return self::SUCCESS;
    }
}
