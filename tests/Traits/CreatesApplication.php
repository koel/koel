<?php

namespace Tests\Traits;

use App\Console\Kernel;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    protected string $mediaPath = __DIR__ . '/../songs';
    private Kernel $artisan;
    protected string $baseUrl = 'http://localhost';

    public function createApplication(): Application
    {
        $this->mediaPath = realpath($this->mediaPath);

        /** @var Application $app */
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $this->artisan = $app->make(Artisan::class);
        $this->artisan->bootstrap();

        return $app;
    }

    private function prepareForTests(): void
    {
        $this->artisan->call('migrate');

        if (!User::query()->count()) {
            $this->artisan->call('db:seed');
        }
    }
}
