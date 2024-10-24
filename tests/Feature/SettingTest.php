<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\MediaScanner;
use App\Values\ScanResultCollection;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class SettingTest extends TestCase
{
    private MediaScanner|MockInterface $mediaScanner;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaScanner = self::mock(MediaScanner::class);
    }

    #[Test]
    public function saveSettings(): void
    {
        $this->mediaScanner->shouldReceive('scan')->once()
            ->andReturn(ScanResultCollection::create());

        $this->putAs('/api/settings', ['media_path' => __DIR__], create_admin())
            ->assertSuccessful();

        self::assertSame(__DIR__, Setting::get('media_path'));
    }

    #[Test]
    public function nonAdminCannotSaveSettings(): void
    {
        $this->putAs('/api/settings', ['media_path' => __DIR__])
            ->assertForbidden();
    }

    #[Test]
    public function mediaPathCannotBeSetForCloudStorage(): void
    {
        config(['koel.storage_driver' => 's3']);

        $this->putAs('/api/settings', ['media_path' => __DIR__], create_admin())
            ->assertUnprocessable();

        config(['koel.storage_driver' => 'local']);
    }
}
