<?php

namespace Tests\Traits;

use App\Console\Kernel;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

trait CreatesApplication
{
    protected string $mediaPath = __DIR__ . '/../songs';
    private Kernel $artisan;
    protected string $baseUrl = 'http://localhost';
    public static bool $migrated = false;

    public function createApplication(): Application
    {
        $this->mediaPath = realpath($this->mediaPath);

        /** @var Application $app */
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $this->artisan = $app->make(Artisan::class);
        $this->artisan->bootstrap();

        // Unless the DB is stored in memory, we need to migrate the DB only once for the whole test suite.
        if (!CreatesApplication::$migrated || DB::connection()->getDatabaseName() === ':memory:') {
            $this->artisan->call('migrate');

            if (!User::query()->count()) {
                $this->artisan->call('db:seed');
            }

            CreatesApplication::$migrated = true;
        }

        return $app;
    }
}
