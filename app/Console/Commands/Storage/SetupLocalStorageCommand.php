<?php

namespace App\Console\Commands\Storage;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Jackiedo\DotenvEditor\DotenvEditor;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

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
        info('Setting up local storage for Koel.');
        warning('Changing the storage configuration can cause irreversible data loss.');
        warning('Consider backing up your data before proceeding.');

        Setting::set('media_path', $this->askForMediaPath());

        $this->dotenvEditor->setKey('STORAGE_DRIVER', 'local');
        $this->dotenvEditor->save();
        Artisan::call('config:clear', ['--quiet' => true]);

        info('Local storage has been set up.');

        if (confirm(label: 'Would you want to initialize a scan now?', default: true)) {
            $this->call('koel:scan');
        }

        return self::SUCCESS;
    }

    private function askForMediaPath(): string
    {
        $mediaPath = text(
            label: 'Enter the absolute path to your media files',
            default: Setting::get('media_path') ?? '',
        );

        if (File::isReadable($mediaPath) && File::isWritable($mediaPath)) {
            return $mediaPath;
        }

        error('The path you entered is not read- and/or writeable. Please check and try again.');

        return $this->askForMediaPath();
    }
}
