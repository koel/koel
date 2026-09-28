<?php

namespace Tests\Feature\Commands;

use App\Services\DotenvEditor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
        $this->mock(DotenvEditor::class)->shouldNotReceive('setKey');

        $this->artisan('koel:init', ['--no-interaction' => true])->assertFailed();
    }
}
