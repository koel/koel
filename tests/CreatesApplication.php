<?php

namespace Tests;

use App\Console\Kernel;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    protected $coverPath;
    protected $mediaPath = __DIR__.'/songs';

    /** @var Kernel */
    private $artisan;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $this->artisan = $app->make(Artisan::class);
        $this->artisan->bootstrap();

        $this->coverPath = $app->basePath().'/public/img/covers';

        return $app;
    }

    private function prepareForTests()
    {
        $this->artisan->call('migrate');

        if (!User::all()->count()) {
            $this->artisan->call('db:seed');
        }

        if (!file_exists($this->coverPath)) {
            mkdir($this->coverPath, 0777, true);
        }
    }
}
