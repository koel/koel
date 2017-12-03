<?php

namespace App\Console\Commands;

use App\Libraries\WatchRecord\InotifyWatchRecord;
use App\Models\File;
use App\Models\Setting;
use Illuminate\Console\Command;
use Media;

class SyncMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'koel:sync
        {record? : A single watch record. Consult Wiki for more info.}
        {--tags= : The comma-separated tags to sync into the database}
        {--force : Force re-syncing even unchanged files}';

    protected $ignored = 0;
    protected $invalid = 0;
    protected $synced = 0;

    /**
     * The progress bar.
     *
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $bar;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync songs found in configured directory against the database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!Setting::get('media_path')) {
            $this->warn("Media path hasn't been configured. Let's set it up.");
            while (true) {
                $path = $this->ask('Absolute path to your media directory:');

                if (is_dir($path) && is_readable($path)) {
                    Setting::set('media_path', $path);
                    break;
                }

                $this->error('The path does not exist or not readable. Try again.');
            }
        }

        if (!$record = $this->argument('record')) {
            $this->syncAll();

            return;
        }

        $this->syngle($record);
    }

    /**
     * Sync all files in the configured media path.
     */
    protected function syncAll()
    {
        $this->info('Koel syncing started.'.PHP_EOL);

        // Get the tags to sync.
        // Notice that this is only meaningful for existing records.
        // New records will have every applicable field sync'ed in.
        $tags = $this->option('tags') ? explode(',', $this->option('tags')) : [];

        Media::sync(null, $tags, $this->option('force'), $this);

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
     */
    public function syngle($record)
    {
        Media::syncByWatchRecord(new InotifyWatchRecord($record), $this);
    }

    /**
     * Log a song's sync status to console.
     *
     * @param string $path
     * @param int    $result
     * @param string $reason
     */
    public function logToConsole($path, $result, $reason = '')
    {
        $name = basename($path);

        if ($result === File::SYNC_RESULT_UNMODIFIED) {
            if ($this->option('verbose')) {
                $this->line("$name has no changes – ignoring");
            }

            $this->ignored++;
        } elseif ($result === File::SYNC_RESULT_BAD_FILE) {
            if ($this->option('verbose')) {
                $this->error("$name is not a valid media file because: ".$reason);
            }

            $this->invalid++;
        } else {
            if ($this->option('verbose')) {
                $this->info("$name synced");
            }

            $this->synced++;
        }
    }

    /**
     * Create a progress bar.
     *
     * @param int $max Max steps
     */
    public function createProgressBar($max)
    {
        $this->bar = $this->getOutput()->createProgressBar($max);
    }

    /**
     * Update the progress bar (advance by 1 step).
     */
    public function updateProgressBar()
    {
        $this->bar->advance();
    }
}
