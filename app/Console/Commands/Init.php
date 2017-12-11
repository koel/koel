<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use MediaCache;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'koel:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install or upgrade Koel';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment('Attempting to install or upgrade Koel.');
        $this->comment('Remember, you can always install/upgrade manually following the guide here:');
        $this->info('📙  '.config('koel.misc.docs_url').PHP_EOL);

        if (!config('app.key')) {
            $this->info('Generating app key');
            Artisan::call('key:generate');
        } else {
            $this->comment('App key exists -- skipping');
        }

        if (!config('jwt.secret')) {
            $this->info('Generating JWT secret');
            Artisan::call('koel:generate-jwt-secret');
        } else {
            $this->comment('JWT secret exists -- skipping');
        }

        $dbSetUp = false;
        while (!$dbSetUp) {
            try {
                // Make sure the config cache is cleared before another attempt.
                Artisan::call('config:clear');
                DB::reconnect()->getPdo();
                $dbSetUp = true;
            } catch (Exception $e) {
                $this->error($e->getMessage());
                $this->warn(PHP_EOL.'Koel cannot connect to the database. Let\'s set it up.');
                $this->setUpDatabase();
            }
        }

        $this->info('Migrating database');
        Artisan::call('migrate', ['--force' => true]);
        // Clean the media cache, just in case we did any media-related migration
        MediaCache::clear();

        if (!User::count()) {
            $this->setUpAdminAccount();
            $this->info('Seeding initial data');
            Artisan::call('db:seed', ['--force' => true]);
        } else {
            $this->comment('Data seeded -- skipping');
        }

        if (!Setting::get('media_path')) {
            $this->setMediaPath();
        }

        $this->info('Compiling front-end stuff');
        system('yarn install');

        $this->comment(PHP_EOL.'🎆  Success! Koel can now be run from localhost with `php artisan serve`.');
        if (Setting::get('media_path')) {
            $this->comment('You can also scan for media with `php artisan koel:sync`.');
        }
        $this->comment('Again, for more configuration guidance, refer to');
        $this->info('📙  '.config('koel.misc.docs_url'));
        $this->comment('or open the .env file in the root installation folder.');
        $this->comment('Thanks for using Koel. You rock!');
    }

    /**
     * Prompt user for valid database credentials and set up the database.
     */
    private function setUpDatabase()
    {
        $config = [
            'DB_CONNECTION' => '',
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_DATABASE' => '',
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
            $config['DB_PORT'] = (string) $this->ask('DB port (leave empty for default)', false);
            $config['DB_DATABASE'] = $this->anticipate('DB name', ['koel']);
            $config['DB_USERNAME'] = $this->anticipate('DB user', ['koel']);
            $config['DB_PASSWORD'] = (string) $this->ask('DB password', false);
        }

        foreach ($config as $key => $value) {
            DotenvEditor::setKey($key, $value);
        }
        DotenvEditor::save();

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

    /**
     * Set up the admin account.
     */
    private function setUpAdminAccount()
    {
        $this->info("Let's create the admin account.");
        $name = $this->ask('Your name');
        $email = $this->ask('Your email address');
        $passwordConfirmed = false;
        while (!$passwordConfirmed) {
            $password = $this->secret('Your desired password');
            $confirmation = $this->secret('Again, just to make sure');
            if ($confirmation !== $password) {
                $this->error('That doesn\'t match. Let\'s try again.');
            } else {
                $passwordConfirmed = true;
            }
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);
    }

    /**
     * Set the media path via the console.
     */
    private function setMediaPath()
    {
        $this->info('The absolute path to your media directory. If this is skipped (left blank) now, you can set it later via the web interface.');

        while (true) {
            $path = $this->ask('Media path', false);
            if ($path === false) {
                return;
            }

            if (is_dir($path) && is_readable($path)) {
                Setting::set('media_path', $path);

                return;
            }

            $this->error('The path does not exist or not readable. Try again.');
        }
    }
}
