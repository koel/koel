<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\MediaSyncService;

class SettingTest extends TestCase
{
    private $mediaSyncService;

    public function setUp(): void
    {
        parent::setUp();
        $this->mediaSyncService = static::mockIocDependency(MediaSyncService::class);
    }

    public function testSaveSettings(): void
    {
        $this->mediaSyncService->shouldReceive('sync')->once();

        $user = factory(User::class)->states('admin')->create();
        $this->postAsUser('/api/settings', ['media_path' => __DIR__], $user);

        self::assertEquals(__DIR__, Setting::get('media_path'));
    }
}
