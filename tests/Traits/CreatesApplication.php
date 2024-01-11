<?php

namespace Tests\Traits;

use App\Console\Kernel;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

use function Tests\test_path;

trait CreatesApplication
{
    protected string $mediaPath;
    private Kernel $artisan;
    protected string $baseUrl = 'http://localhost';
    public static bool $migrated = false;

    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $this->mediaPath = test_path('songs');

        /** @var Kernel $artisan */
        $artisan = $app->make(Artisan::class);

        $this->artisan = $artisan;
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
