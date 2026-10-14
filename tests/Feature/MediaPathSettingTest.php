<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Scanners\DirectoryScanner;
use App\Values\Scanning\ScanSummary;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class MediaPathSettingTest extends TestCase
{
    private DirectoryScanner|MockInterface $mediaScanner;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaScanner = $this->mock(DirectoryScanner::class);
    }

    #[Test]
    public function saveSettings(): void
    {
        $this->mediaScanner->expects('scan')->andReturn(ScanSummary::make('scan-id', 0, 0, 0));

        $this->putAs('/api/settings/media-path', ['path' => __DIR__], create_admin())->assertSuccessful();

        self::assertSame(__DIR__, Setting::get('media_path'));
    }

    #[Test]
    public function nonAdminCannotSaveSettings(): void
    {
        $this->putAs('/api/settings/media-path', ['path' => __DIR__])->assertForbidden();
    }

    #[Test]
    public function mediaPathCannotBeSetForCloudStorage(): void
    {
        config(['koel.storage_driver' => 's3']);

        $this->putAs('/api/settings/media-path', ['path' => __DIR__], create_admin())->assertUnprocessable();

        config(['koel.storage_driver' => 'local']);
    }
}
