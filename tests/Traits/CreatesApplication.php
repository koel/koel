<?php

namespace Tests\Traits;

use App\Console\Kernel;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    protected $mediaPath = __DIR__ . '/../songs';

    /** @var Kernel */
    private $artisan;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $this->artisan = $app->make(Artisan::class);
        $this->artisan->bootstrap();

        return $app;
    }

    private function prepareForTests(): void
    {
        $this->artisan->call('migrate');

        if (!User::count()) {
            $this->artisan->call('db:seed');
        }
    }
}
