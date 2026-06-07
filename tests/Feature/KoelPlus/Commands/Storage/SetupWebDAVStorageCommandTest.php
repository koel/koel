<?php

namespace Tests\Feature\KoelPlus\Commands\Storage;

use App\Services\DotenvEditor;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class SetupWebDAVStorageCommandTest extends PlusTestCase
{
    #[Test]
    public function setsUpWebDavStorage(): void
    {
        Storage::fake('webdav');

        /** @var DotenvEditor&MockInterface $dotenv */
        $dotenv = $this->mock(DotenvEditor::class);
        $dotenv->shouldReceive('backup')->once()->andReturnSelf();
        $dotenv
            ->shouldReceive('setKeys')
            ->once()
            ->with([
                'STORAGE_DRIVER' => 'webdav',
                'WEBDAV_BASE_URL' => 'https://nc.example.com/remote.php/dav/files/me/',
                'WEBDAV_USERNAME' => 'me',
                'WEBDAV_PASSWORD' => 'app-password',
                'WEBDAV_PATH_PREFIX' => 'Music',
            ]);

        $this
            ->artisan('koel:storage:webdav')
            ->expectsQuestion('Enter your WebDAV base URL', 'https://nc.example.com/remote.php/dav/files/me')
            ->expectsQuestion('Enter your WebDAV username', 'me')
            ->expectsQuestion('Enter your WebDAV password', 'app-password')
            ->expectsQuestion('Optional path prefix beneath the base URL', '/Music/')
            ->assertSuccessful();
    }
}
