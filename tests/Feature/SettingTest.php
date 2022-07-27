<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\MediaSyncService;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;

class SettingTest extends TestCase
{
    private MediaSyncService|MockInterface|LegacyMockInterface $mediaSyncService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaSyncService = self::mock(MediaSyncService::class);
    }

    public function testSaveSettings(): void
    {
        $this->mediaSyncService->shouldReceive('sync')->once();

        $this->putAs('/api/settings', ['media_path' => __DIR__], User::factory()->admin()->create())
            ->assertSuccessful();

        self::assertEquals(__DIR__, Setting::get('media_path'));
    }

    public function testNonAdminCannotSaveSettings(): void
    {
        $this->putAs('/api/settings', ['media_path' => __DIR__], User::factory()->create())
            ->assertForbidden();
    }
}
