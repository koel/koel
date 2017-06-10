<?php

namespace Tests;

use App\Models\User;
use Artisan;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    protected $coverPath;
    protected $mediaPath = __DIR__.'/songs';

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->coverPath = $app->basePath().'/public/img/covers';

        return $app;
    }

    private function prepareForTests()
    {
        Artisan::call('migrate');

        if (!User::all()->count()) {
            Artisan::call('db:seed');
        }

        if (!file_exists($this->coverPath)) {
            mkdir($this->coverPath, 0777, true);
        }
    }
}
