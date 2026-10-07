<?php

namespace Tests\Feature\KoelPlus\Commands\Storage;

use App\Services\DotenvEditor;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class SetupS3StorageCommandTest extends PlusTestCase
{
    #[Test]
    public function blankSecretKeepsTheCurrentOneEvenWithACachedConfig(): void
    {
        Storage::fake('s3');
        config(['filesystems.disks.s3.secret' => 'current-secret']);

        /** @var DotenvEditor&MockInterface $dotenv */
        $dotenv = $this->mock(DotenvEditor::class);
        $dotenv->shouldReceive('backup')->once()->andReturnSelf();
        $dotenv
            ->shouldReceive('setKeys')
            ->once()
            ->with(Mockery::subset(['AWS_SECRET_ACCESS_KEY' => 'current-secret']));

        $this
            ->artisan('koel:storage:s3')
            ->expectsQuestion('Enter the access key ID', 'key-id')
            ->expectsQuestion('Enter the secret access key', '')
            ->expectsQuestion('Enter the region', 'auto')
            ->expectsQuestion('Enter the endpoint', 'https://s3.example.com')
            ->expectsQuestion('Enter the bucket name', 'koel')
            ->assertSuccessful();
    }
}
