<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\MediaSyncService;
use Mockery\MockInterface;

class SettingTest extends TestCase
{
    private MockInterface $mediaSyncService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaSyncService = self::mock(MediaSyncService::class);
    }

    public function testSaveSettings(): void
    {
        $this->mediaSyncService->shouldReceive('sync')->once();

        $user = User::factory()->admin()->create();
        $this->putAsUser('/api/settings', ['media_path' => __DIR__], $user);

        self::assertEquals(__DIR__, Setting::get('media_path'));
    }
}
