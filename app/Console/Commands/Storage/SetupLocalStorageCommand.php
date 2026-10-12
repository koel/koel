<?php

namespace App\Console\Commands\Storage;

use App\Models\Setting;
use App\Services\DotenvEditor;
use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class SetupLocalStorageCommand extends SetupStorageCommand
{
    protected $signature = 'koel:storage:local';
    protected $description = 'Set up the local storage for Koel';

    public function __construct(
        DotenvEditor $dotenvEditor,
        private readonly SettingService $settingService,
    ) {
        parent::__construct($dotenvEditor);
    }

    public function handle(): int
    {
        $this->introduceSetup('Setting up local storage for Koel.');

        $this->settingService->updateMediaPath($this->askForMediaPath());

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
