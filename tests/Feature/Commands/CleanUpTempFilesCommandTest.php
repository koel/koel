<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CleanUpTempFilesCommandTest extends TestCase
{
    private string $tmpDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = artifact_path('tmp');
        File::ensureDirectoryExists($this->tmpDir);
    }

    #[Test]
    public function deleteOldTempFiles(): void
    {
        $oldFile = $this->tmpDir . '/old-file.tmp';
        File::put($oldFile, 'old content');
        touch($oldFile, now()->subMinutes(1500)->timestamp);

        $newFile = $this->tmpDir . '/new-file.tmp';
        File::put($newFile, 'new content');
        touch($newFile, now()->timestamp);

        $this->artisan('koel:clean-up-temp-files')->assertSuccessful();

        self::assertFileDoesNotExist($oldFile);
        self::assertFileExists($newFile);
    }

    #[Test]
    public function respectCustomAgeOption(): void
    {
        $file = $this->tmpDir . '/medium-file.tmp';
        File::put($file, 'content');
        touch($file, now()->subMinutes(30)->timestamp);

        $this->artisan('koel:clean-up-temp-files', ['--age' => 20])->assertSuccessful();

        self::assertFileDoesNotExist($file);
    }

    #[Test]
    public function reportWhenNoFilesToDelete(): void
    {
        $this->artisan('koel:clean-up-temp-files')->assertSuccessful();
    }
}
