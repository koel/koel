<?php

namespace App\Console\Commands;

use App\Libraries\WatchRecord\InotifyWatchRecord;
use App\Models\Setting;
use App\Repositories\SettingRepository;
use App\Services\FileSynchronizer;
use App\Services\MediaSyncService;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class SyncCommand extends Command
{
    protected $signature = 'koel:sync
        {record? : A single watch record. Consult Wiki for more info.}
        {--tags= : The comma-separated tags to sync into the database}
        {--force : Force re-syncing even unchanged files}';

    protected $description = 'Sync songs found in configured directory against the database.';
    private $ignored = 0;
    private $invalid = 0;
    private $synced = 0;
    private $mediaSyncService;
    private $settingRepository;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    public function __construct(MediaSyncService $mediaSyncService, SettingRepository $settingRepository)
    {
        parent::__construct();
        $this->mediaSyncService = $mediaSyncService;
        $this->settingRepository = $settingRepository;
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->ensureMediaPath();

        if (!$record = $this->argument('record')) {
            $this->syncAll();

            return;
        }

        $this->syngle($record);
    }

    /**
     * Sync all files in the configured media path.
     *
     * @throws Exception
     */
    protected function syncAll(): void
    {
        $this->info('Syncing media from '.Setting::get('media_path').PHP_EOL);

        // Get the tags to sync.
        // Notice that this is only meaningful for existing records.
        // New records will have every applicable field sync'ed in.
        $tags = $this->option('tags') ? explode(',', $this->option('tags')) : [];

        $this->mediaSyncService->sync(null, $tags, $this->option('force'), $this);

        $this->output->writeln(
            PHP_EOL.PHP_EOL
            ."<info>Completed! {$this->synced} new or updated song(s)</info>, "
            ."{$this->ignored} unchanged song(s), "
            ."and <comment>{$this->invalid} invalid file(s)</comment>."
        );
    }

    /**
     * SYNc a sinGLE file or directory. See my awesome pun?
     *
     * @param string $record The watch record.
     *                       As of current we only support inotifywait.
     *                       Some examples:
     *                       - "DELETE /var/www/media/gone.mp3"
     *                       - "CLOSE_WRITE,CLOSE /var/www/media/new.mp3"
     *                       - "MOVED_TO /var/www/media/new_dir"
     *
     * @link http://man7.org/linux/man-pages/man1/inotifywait.1.html
     *
     * @throws Exception
     */
    public function syngle(string $record): void
    {
        $this->mediaSyncService->syncByWatchRecord(new InotifyWatchRecord($record));
    }

    /**
     * Log a song's sync status to console.
     */
    public function logSyncStatusToConsole(string $path, int $result, ?string $reason = null): void
    {
        $name = basename($path);

        if ($result === FileSynchronizer::SYNC_RESULT_UNMODIFIED) {
            $this->ignored++;
        } elseif ($result === FileSynchronizer::SYNC_RESULT_BAD_FILE) {
            if ($this->option('verbose')) {
                $this->error(PHP_EOL."'$name' is not a valid media file: ".$reason);
            }

            $this->invalid++;
        } else {
            $this->synced++;
        }
    }

    public function createProgressBar(int $max): void
    {
        $this->progressBar = $this->getOutput()->createProgressBar($max);
    }

    public function advanceProgressBar(): void
    {
        $this->progressBar->advance();
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
