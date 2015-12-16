<?php

namespace App\Console\Commands;

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
    protected $signature = 'koel:sync';

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

        $this->info('Koel syncing started. All we need now is just a little patience…');

        Media::sync(null, $this);

        $this->output->writeln("<info>Completed! {$this->synced} new or updated songs(s)</info>, "
            ."{$this->ignored} unchanged song(s), "
            ."and <comment>{$this->invalid} invalid file(s)</comment>.");
    }


    /**
     * Log a song's sync status to console.
     */
    public function logToConsole($path, $result)
    {
        $name = basename($path);

        if (is_null($result)) {
            if ($this->option('verbose')) {
                $this->info("$name synced");
            }

            return ++$this->synced;
        }

        if ($result === true) {
            if ($this->option('verbose')) {
                $this->line("$name has no changes – ignoring");
            }

            return ++$this->ignored;
        }

        if ($this->option('verbose')) {
            $this->error("$name is not a valid media file");
        }

        ++$this->invalid;
    }
}
