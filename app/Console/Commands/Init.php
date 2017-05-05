<?php

namespace App\Console\Commands;

use App\Models\User;
use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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
        try {
            DB::connection();
        } catch (Exception $e) {
            $this->error('Unable to connect to database.');
            $this->error('Please fill valid database credentials into .env and rerun this command.');

            return;
        }

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

        $this->info('Migrating database');
        Artisan::call('migrate', ['--force' => true]);
        // Clean the media cache, just in case we did any media-related migration
        MediaCache::clear();

        if (!User::count()) {
            $this->info('Seeding initial data');
            Artisan::call('db:seed', ['--force' => true]);
        } else {
            $this->comment('Data seeded -- skipping');
        }

        $this->info('Compiling front-end stuff');
        system('yarn install');

        $this->comment(PHP_EOL.'🎆  Success! You can now run Koel from localhost with `php artisan serve`.');
        $this->comment('Again, for more configuration guidance, refer to');
        $this->info('📙  '.config('koel.misc.docs_url'));
        $this->comment('Thanks for using Koel. You rock!');
    }
}
