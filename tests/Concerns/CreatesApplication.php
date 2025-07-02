<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;

use function Tests\test_path;

trait CreatesApplication
{
    protected string $mediaPath;
    protected string $baseUrl = 'http://localhost';

    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $this->mediaPath = test_path('songs');

        $artisan = $app->make(Artisan::class);
        $artisan->bootstrap();

        return $app;
    }
}
