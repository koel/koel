<?php

namespace App\Console\Commands\Storage;

use App\Facades\License;
use App\Services\DotenvEditor;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

use function Laravel\Prompts\password;

abstract class SetupStorageCommand extends Command
{
    public function __construct(
        protected readonly DotenvEditor $dotenvEditor,
    ) {
        parent::__construct();
    }

    protected function ensureKoelPlus(string $driverName): bool
    {
        if (License::isPlus()) {
            return true;
        }

        $this->components->error("$driverName as a storage driver is only available in Koel Plus.");

        return false;
    }

    protected function introduceSetup(string $description): void
    {
        $this->components->info($description);
        $this->components->warn('Changing the storage configuration can cause irreversible data loss.');
        $this->components->warn('Consider backing up your data before proceeding.');
    }

    protected static function askForSecret(string $label, string $currentValue, string $hint = ''): string
    {
        $enteredValue = password(
            label: $label,
            hint: $currentValue === '' ? $hint : trim("$hint Leave blank to keep the current one."),
        );

        return $enteredValue !== '' ? $enteredValue : $currentValue;
    }

    /**
     * Save the new configuration to .env and upload a test file. If the upload fails, the previous .env is restored.
     *
     * @param array<string, mixed> $config
     */
    protected function saveAndVerifyConfig(array $config, Closure $uploadTestFile, string $failureHint): bool
    {
        $this->dotenvEditor->backup()->setKeys($config);

        $this->comment('Uploading a test file to make sure everything is working...');

        try {
            $uploadTestFile();
        } catch (Throwable $e) {
            $this->error("Failed to upload a test file: {$e->getMessage()}.");
            $this->comment($failureHint);

            $this->dotenvEditor->restore();
            Artisan::call('config:clear', ['--quiet' => true]);

            return false;
        }

        return true;
    }
}
