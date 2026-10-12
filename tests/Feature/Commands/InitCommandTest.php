<?php

namespace Tests\Feature\Commands;

use App\Exceptions\EnvFileIsDirectoryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InitCommandTest extends TestCase
{
    private string $environmentPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->environmentPath = sys_get_temp_dir() . '/' . Str::uuid();
        File::makeDirectory($this->environmentPath . '/.env', recursive: true);
        $this->app->useEnvironmentPath($this->environmentPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->environmentPath);

        parent::tearDown();
    }

    #[Test]
    public function failWhenEnvFileIsDirectory(): void
    {
        Log::spy();

        $this->artisan('koel:init', ['--no-interaction' => true])->assertFailed();

        Log::shouldHaveReceived('error')->once()->with(Mockery::type(EnvFileIsDirectoryException::class));
    }
}
