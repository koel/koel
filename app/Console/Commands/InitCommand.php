<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\AskForPassword;
use App\Exceptions\InstallationFailedException;
use App\Models\Setting;
use App\Models\User;
use App\Services\MediaCacheService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Contracts\Hashing\Hasher as Hash;
use Illuminate\Database\DatabaseManager as DB;
use Illuminate\Support\Facades\Log;
use Jackiedo\DotenvEditor\DotenvEditor;
use Throwable;

class InitCommand extends Command
{
    use AskForPassword;

    private const DEFAULT_ADMIN_NAME = 'Koel';
    private const DEFAULT_ADMIN_EMAIL = 'admin@koel.dev';
    private const DEFAULT_ADMIN_PASSWORD = 'KoelIsCool';
    private const NON_INTERACTION_MAX_ATTEMPT_COUNT = 10;

    protected $signature = 'koel:init {--no-assets}';
    protected $description = 'Install or upgrade Koel';

    private bool $adminSeeded = false;

    public function __construct(
        private MediaCacheService $mediaCacheService,
        private Artisan $artisan,
        private Hash $hash,
        private DotenvEditor $dotenvEditor,
        private DB $db
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->comment('Attempting to install or upgrade Koel.');
        $this->comment('Remember, you can always install/upgrade manually following the guide here:');
        $this->info('📙  ' . config('koel.misc.docs_url') . PHP_EOL);

        if ($this->inNoInteractionMode()) {
            $this->info('Running in no-interaction mode');
        }

        try {
            $this->maybeGenerateAppKey();
            $this->maybeSetUpDatabase();
            $this->migrateDatabase();
            $this->maybeSeedDatabase();
            $this->maybeSetMediaPath();
            $this->maybeCompileFrontEndAssets();
        } catch (Throwable $e) {
            Log::error($e);

            $this->error("Oops! Koel installation or upgrade didn't finish successfully.");
            $this->error('Please try again, or visit ' . config('koel.misc.docs_url') . ' for manual installation.');
            $this->error('😥 Sorry for this. You deserve better.');

            return self::FAILURE;
        }

        $this->comment(PHP_EOL . '🎆  Success! Koel can now be run from localhost with `php artisan serve`.');

        if ($this->adminSeeded) {
            $this->comment(
                sprintf('Log in with email %s and password %s', self::DEFAULT_ADMIN_EMAIL, self::DEFAULT_ADMIN_PASSWORD)
            );
        }

        if (Setting::get('media_path')) {
            $this->comment('You can also scan for media with `php artisan koel:sync`.');
        }

        $this->comment('Again, visit 📙 ' . config('koel.misc.docs_url') . ' for the official documentation.');

        $this->comment(
            "Feeling generous and want to support Koel's development? Check out "
            . config('koel.misc.sponsor_github_url')
            . ' 🤗'
        );

        $this->comment('Thanks for using Koel. You rock! 🤘');

        return self::SUCCESS;
    }

    /**
     * Prompt user for valid database credentials and set up the database.
     */
    private function setUpDatabase(): void
    {
        $config = [
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ];

        $config['DB_CONNECTION'] = $this->choice(
            'Your DB driver of choice',
            [
                'mysql' => 'MySQL/MariaDB',
                'pgsql' => 'PostgreSQL',
                'sqlsrv' => 'SQL Server',
                'sqlite-e2e' => 'SQLite',
            ],
            'mysql'
        );

        if ($config['DB_CONNECTION'] === 'sqlite-e2e') {
            $config['DB_DATABASE'] = $this->ask('Absolute path to the DB file');
        } else {
            $config['DB_HOST'] = $this->anticipate('DB host', ['127.0.0.1', 'localhost']);
            $config['DB_PORT'] = (string) $this->ask('DB port (leave empty for default)');
            $config['DB_DATABASE'] = $this->anticipate('DB name', ['koel']);
            $config['DB_USERNAME'] = $this->anticipate('DB user', ['koel']);
            $config['DB_PASSWORD'] = (string) $this->ask('DB password');
        }

        foreach ($config as $key => $value) {
            $this->dotenvEditor->setKey($key, $value);
        }

        $this->dotenvEditor->save();

        // Set the config so that the next DB attempt uses refreshed credentials
        config([
            'database.default' => $config['DB_CONNECTION'],
            "database.connections.{$config['DB_CONNECTION']}.host" => $config['DB_HOST'],
            "database.connections.{$config['DB_CONNECTION']}.port" => $config['DB_PORT'],
            "database.connections.{$config['DB_CONNECTION']}.database" => $config['DB_DATABASE'],
            "database.connections.{$config['DB_CONNECTION']}.username" => $config['DB_USERNAME'],
            "database.connections.{$config['DB_CONNECTION']}.password" => $config['DB_PASSWORD'],
        ]);
    }

    private function inNoInteractionMode(): bool
    {
        return (bool) $this->option('no-interaction');
    }

    private function inNoAssetsMode(): bool
    {
        return (bool) $this->option('no-assets');
    }

    private function setUpAdminAccount(): void
    {
        $this->info("Creating default admin account");

        User::create([
            'name' => self::DEFAULT_ADMIN_NAME,
            'email' => self::DEFAULT_ADMIN_EMAIL,
            'password' => $this->hash->make(self::DEFAULT_ADMIN_PASSWORD),
            'is_admin' => true,
        ]);

        $this->adminSeeded = true;
    }

    private function maybeSetMediaPath(): void
    {
        if (Setting::get('media_path')) {
            return;
        }

        if ($this->inNoInteractionMode()) {
            $this->setMediaPathFromEnvFile();

            return;
        }

        $this->info('The absolute path to your media directory. If this is skipped (left blank) now, you can set it later via the web interface.'); // @phpcs-ignore-line

        while (true) {
            $path = $this->ask('Media path', config('koel.media_path'));

            if (!$path) {
                return;
            }

            if (self::isValidMediaPath($path)) {
                Setting::set('media_path', $path);

                return;
            }

            $this->error('The path does not exist or not readable. Try again.');
        }
    }

    private function maybeGenerateAppKey(): void
    {
        if (!config('app.key')) {
            $this->info('Generating app key');
            $this->artisan->call('key:generate');
        } else {
            $this->comment('App key exists -- skipping');
        }
    }

    private function maybeSeedDatabase(): void
    {
        if (!User::count()) {
            $this->setUpAdminAccount();
            $this->info('Seeding initial data');
            $this->artisan->call('db:seed', ['--force' => true]);
        } else {
            $this->comment('Data seeded -- skipping');
        }
    }

    private function maybeSetUpDatabase(): void
    {
        $attemptCount = 0;

        while (true) {
            // In non-interactive mode, we must not endlessly attempt to connect.
            // Doing so will just end up with a huge amount of "failed to connect" logs.
            // We do retry a little, though, just in case there's some kind of temporary failure.
            if ($this->inNoInteractionMode() && $attemptCount >= self::NON_INTERACTION_MAX_ATTEMPT_COUNT) {
                $this->warn("Maximum database connection attempts reached. Giving up.");
                break;
            }

            $attemptCount++;

            try {
                // Make sure the config cache is cleared before another attempt.
                $this->artisan->call('config:clear');
                $this->db->reconnect()->getPdo();

                break;
            } catch (Throwable $e) {
                $this->error($e->getMessage());

                // We only try to update credentials if running in interactive mode.
                // Otherwise, we require admin intervention to fix them.
                // This avoids inadvertently wiping credentials if there's a connection failure.
                if ($this->inNoInteractionMode()) {
                    $warning = sprintf(
                        "%sKoel cannot connect to the database. Attempt: %d/%d",
                        PHP_EOL,
                        $attemptCount,
                        self::NON_INTERACTION_MAX_ATTEMPT_COUNT
                    );
                    $this->warn($warning);
                } else {
                    $this->warn(sprintf("%sKoel cannot connect to the database. Let's set it up.", PHP_EOL));
                    $this->setUpDatabase();
                }
            }
        }
    }

    private function migrateDatabase(): void
    {
        $this->info('Migrating database');
        $this->artisan->call('migrate', ['--force' => true]);

        // Clear the media cache, just in case we did any media-related migration
        $this->mediaCacheService->clear();
    }

    private function maybeCompileFrontEndAssets(): void
    {
        if ($this->inNoAssetsMode()) {
            return;
        }

        $this->info('Now to front-end stuff');

        $runOkOrThrow = static function (string $command): void {
            passthru($command, $status);
            throw_if((bool) $status, InstallationFailedException::class);
        };

        $runOkOrThrow('yarn install --colors');
        $this->info('└── Compiling assets');
        $runOkOrThrow('yarn build --colors');
    }

    private function setMediaPathFromEnvFile(): void
    {
        with(config('koel.media_path'), function (?string $path): void {
            if (!$path) {
                return;
            }

            if (static::isValidMediaPath($path)) {
                Setting::set('media_path', $path);
            } else {
                $this->warn(sprintf('The path %s does not exist or not readable. Skipping.', $path));
            }
        });
    }

    private static function isValidMediaPath(string $path): bool
    {
        return is_dir($path) && is_readable($path);
    }
}
