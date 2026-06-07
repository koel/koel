<?php

namespace App\Console\Commands\Storage;

use App\Models\Setting;
use App\Services\DotenvEditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class SetupLocalStorageCommand extends Command
{
    protected $signature = 'koel:storage:local';
    protected $description = 'Set up the local storage for Koel';

    public function __construct(
        private readonly DotenvEditor $dotenvEditor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('Setting up local storage for Koel.');
        $this->components->warn('Changing the storage configuration can cause irreversible data loss.');
        $this->components->warn('Consider backing up your data before proceeding.');

        Setting::set('media_path', $this->askForMediaPath());

        $this->dotenvEditor->setKey('STORAGE_DRIVER', 'local');
        Artisan::call('config:clear', ['--quiet' => true]);

        $this->components->info('Local storage has been set up.');

        if (confirm(label: 'Would you want to initialize a scan now?')) {
            $this->call('koel:scan');
        }

        return self::SUCCESS;
    }

    private function askForMediaPath(): string
    {
        $mediaPath = text(
            label: 'Enter the absolute path to your media files',
            default: (string) Setting::get('media_path'),
            hint: 'The path must exist and be readable and writable by the web server user.',
        );

        if (File::isReadable($mediaPath) && File::isWritable($mediaPath)) {
            return $mediaPath;
        }

        $this->components->error('The path you entered is not read- and/or writeable. Please check and try again.');

        return $this->askForMediaPath();
    }
}
