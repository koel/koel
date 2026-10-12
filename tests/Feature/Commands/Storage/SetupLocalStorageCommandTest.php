<?php

namespace Tests\Feature\Commands\Storage;

use App\Models\Setting;
use App\Services\DotenvEditor;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetupLocalStorageCommandTest extends TestCase
{
    #[Test]
    public function storesCanonicalMediaPath(): void
    {
        $dotenvEditor = Mockery::mock(DotenvEditor::class);
        $dotenvEditor->expects('setKey')->with('STORAGE_DRIVER', 'local');
        $this->instance(DotenvEditor::class, $dotenvEditor);

        $this
            ->artisan('koel:storage:local')
            ->expectsQuestion('Enter the absolute path to your media files', $this->mediaPath . '//')
            ->expectsConfirmation('Would you want to initialize a scan now?', 'no')
            ->assertSuccessful();

        self::assertSame(realpath($this->mediaPath), Setting::get('media_path'));
    }
}
