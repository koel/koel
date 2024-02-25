<?php

namespace App\Console\Commands\Storage;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jackiedo\DotenvEditor\DotenvEditor;

class SetupLocalStorageCommand extends Command
{
    protected $signature = 'koel:storage:local';
    protected $description = 'Set up local storage for Koel';

    public function __construct(private DotenvEditor $dotenvEditor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('Setting up local storage for Koel.');
        $this->components->warn('Changing the storage configuration can cause irreversible data loss.');
        $this->components->warn('Consider backing up your data before proceeding.');

        Setting::set('media_path', $this->askForMediaPath());

        $this->dotenvEditor->setKey('STORAGE_DRIVER', 'local');
        $this->dotenvEditor->save();
        Artisan::call('config:clear', ['--quiet' => true]);

        $this->components->info('Local storage has been set up.');

        if ($this->components->confirm('Would you want to initialize a scan now?')) {
            $this->call('koel:scan');
        }

        return self::SUCCESS;
    }

    private function askForMediaPath(): string
    {
        $mediaPath = $this->components->ask('Enter the absolute path to your media files', Setting::get('media_path'));

        if (File::isReadable($mediaPath) && File::isWritable($mediaPath)) {
            return $mediaPath;
        }

        $this->components->error('The path you entered is not read- and/or writeable. Please check and try again.');

        return $this->askForMediaPath();
    }
}
