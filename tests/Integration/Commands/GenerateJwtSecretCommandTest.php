<?php

namespace Tests\Integration\Commands;

use App\Console\Commands\GenerateJwtSecretCommand;
use App\Console\Kernel;
use Jackiedo\DotenvEditor\DotenvEditor;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GenerateJwtSecretCommandTest extends TestCase
{
    /** @var DotenvEditor|MockInterface */
    private $dotenvEditor;

    /** @var GenerateJwtSecretCommand */
    private $command;

    public function setUp(): void
    {
        parent::setUp();

        $this->dotenvEditor = static::mockIocDependency(DotenvEditor::class);
        $this->command = app(GenerateJwtSecretCommand::class);
        app(Kernel::class)->registerCommand($this->command);
    }

    public function testGenerateJwtSecret(): void
    {
        config(['jwt.secret' => false]);

        $this->dotenvEditor
            ->shouldReceive('setKey')
            ->with('JWT_SECRET', Mockery::on(function ($key) {
                return preg_match('/[a-f0-9]{32}$/i', $key);
            }));

        $this->artisan('koel:generate-jwt-secret');
    }

    public function testNotRegenerateJwtSecret(): void
    {
        config(['jwt.secret' => '12345678901234567890123456789012']);

        $this->dotenvEditor
            ->shouldReceive('setKey')
            ->never();

        $this->artisan('koel:generate-jwt-secret');
    }
}
