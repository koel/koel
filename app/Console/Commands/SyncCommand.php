<?php

namespace App\Console\Commands;

use App\Libraries\WatchRecord\InotifyWatchRecord;
use App\Models\Setting;
use App\Services\MediaSyncService;
use App\Values\SyncResult;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

class SyncCommand extends Command
{
    protected $signature = 'koel:sync
        {record? : A single watch record. Consult Wiki for more info.}
        {--ignore= : The comma-separated tags to ignore (exclude) from syncing}
        {--force : Force re-syncing even unchanged files}';

    protected $description = 'Sync songs found in configured directory against the database.';

    private ProgressBar $progressBar;

    public function __construct(private MediaSyncService $mediaSyncService)
    {
        parent::__construct();

        $this->mediaSyncService->on('paths-gathered', function (array $paths): void {
            $this->progressBar = new ProgressBar($this->output, count($paths));
        });

        $this->mediaSyncService->on('progress', [$this, 'onSyncProgress']);
    }

    public function handle(): int
    {
        $this->ensureMediaPath();

        $record = $this->argument('record');

        if ($record) {
            $this->syncSingleRecord($record);
        } else {
            $this->syncAll();
        }

        return self::SUCCESS;
    }

    /**
     * Sync all files in the configured media path.
     */
    private function syncAll(): void
    {
        $path = Setting::get('media_path');

        $this->components->info('Scanning ' . $path);

        // The tags to ignore from syncing.
        // Notice that this is only meaningful for existing records.
        // New records will have every applicable field synced in.
        $ignores = $this->option('ignore') ? explode(',', $this->option('ignore')) : [];

        $results = $this->mediaSyncService->sync($ignores, $this->option('force'));

        $this->newLine(2);
        $this->components->info('Scanning completed!');

        $this->components->bulletList([
            "<fg=green>{$results->success()->count()}</> new or updated song(s)",
            "<fg=yellow>{$results->skipped()->count()}</> unchanged song(s)",
            "<fg=red>{$results->error()->count()}</> invalid file(s)",
        ]);
    }

    /**
     * @param string $record The watch record.
     *                       As of current we only support inotifywait.
     *                       Some examples:
     *                       - "DELETE /var/www/media/gone.mp3"
     *                       - "CLOSE_WRITE,CLOSE /var/www/media/new.mp3"
     *                       - "MOVED_TO /var/www/media/new_dir"
     *
     * @see http://man7.org/linux/man-pages/man1/inotifywait.1.html
     */
    private function syncSingleRecord(string $record): void
    {
        $this->mediaSyncService->syncByWatchRecord(new InotifyWatchRecord($record));
    }

    public function onSyncProgress(SyncResult $result): void
    {
        if (!$this->option('verbose')) {
            $this->progressBar->advance();

            return;
        }

        $path = dirname($result->path);
        $file = basename($result->path);
        $sep = DIRECTORY_SEPARATOR;

        $this->components->twoColumnDetail("<fg=gray>$path$sep</>$file", match (true) {
            $result->isSuccess() => "<fg=green>OK</>",
            $result->isSkipped() => "<fg=yellow>SKIPPED</>",
            $result->isError() => "<fg=red>ERROR</>",
            default => throw new RuntimeException("Unknown sync result type: {$result->type}")
        });

        if ($result->isError()) {
            $this->output->writeln("<fg=red>$result->error</>");
        }
    }

    private function ensureMediaPath(): void
    {
        if (Setting::get('media_path')) {
            return;
        }

        $this->warn("Media path hasn't been configured. Let's set it up.");

        while (true) {
            $path = $this->ask('Absolute path to your media directory');

            if (is_dir($path) && is_readable($path)) {
                Setting::set('media_path', $path);
                break;
            }

            $this->error('The path does not exist or is not readable. Try again.');
        }
    }
}
