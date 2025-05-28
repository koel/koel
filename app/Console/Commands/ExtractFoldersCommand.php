<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Song;
use App\Services\MediaBrowser;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class ExtractFoldersCommand extends Command
{
    protected $signature = 'koel:extract-folders';
    protected $description = 'Extract the folder structure from the existing song paths and store it in the database';

    private ProgressBar $progressBar;

    public function __construct(private readonly MediaBrowser $browser)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (config('koel.storage_driver') !== 'local') {
            $this->components->error('This command only works with the local storage driver.');

            return self::INVALID;
        }

        $root = Setting::get('media_path');

        if (!$root) {
            $this->components->error('The media path is not set. Please set it up first.');

            return self::INVALID;
        }

        $songs = Song::query()
            ->orderBy('path')
            ->whereNull('podcast_id')
            ->whereNull('folder_id')
            ->storedLocally()
            ->get();

        $this->progressBar = new ProgressBar($this->output, count($songs));

        $this->components->info('Extracting folders from the song paths...');

        $songs->each(function (Song $song): void {
            $this->browser->maybeCreateFolderStructureForSong($song);
            $this->progressBar->advance();
        });

        $this->progressBar->finish();
        $this->output->success('Done!');

        return self::SUCCESS;
    }
}
