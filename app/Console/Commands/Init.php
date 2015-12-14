<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize a new koel project.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('php artisan key:generate');

        Artisan::call('key:generate');

        $this->info('php artisan migrate --force');

        Artisan::call('migrate', ['--force' => true]);

        $this->info('php artisan db:seed --force');

        Artisan::call('db:seed', ['--force' => true]);

        $this->comment("\nProject initialized.");
    }
}
