<?php

namespace App\Console\Commands;

use App\Libraries\WatchRecord\InotifyWatchRecord;
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
        {record? : A single watch record. Consult Wiki for more info.}';

    protected $ignored = 0;
    protected $invalid = 0;
    protected $synced = 0;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync songs found in configured directory against the database.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!Setting::get('media_path')) {
            $this->error("Media path hasn't been configured. Exiting.");

            return;
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
        $this->info('Koel syncing started. All we need now is just a little patience…');

        Media::sync(null, $this);

        $this->output->writeln("<info>Completed! {$this->synced} new or updated song(s)</info>, "
            ."{$this->ignored} unchanged song(s), "
            ."and <comment>{$this->invalid} invalid file(s)</comment>.");
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
     * @see http://man7.org/linux/man-pages/man1/inotifywait.1.html
     */
    public function syngle($record)
    {
        Media::syncByWatchRecord(new InotifyWatchRecord($record), $this);
    }

    /**
     * Log a song's sync status to console.
     */
    public function logToConsole($path, $result)
    {
        $name = basename($path);

        if ($result === true) {
            if ($this->option('verbose')) {
                $this->line("$name has no changes – ignoring");
            }

            ++$this->ignored;
        } elseif ($result === false) {
            if ($this->option('verbose')) {
                $this->error("$name is not a valid media file");
            }

            ++$this->invalid;
        } else {
            if ($this->option('verbose')) {
                $this->info("$name synced");
            }

            ++$this->synced;
        }
    }
}
