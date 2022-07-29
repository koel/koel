<?php

namespace Tests\Traits;

use App\Console\Kernel;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;
use Throwable;

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
        try {
            $this->artisan->call('migrate');
        } catch (Throwable $e) {
            \Log::error($e); // @phpcs:ignore
            throw $e;
        }

        if (!User::count()) {
            $this->artisan->call('db:seed');
        }
    }
}
