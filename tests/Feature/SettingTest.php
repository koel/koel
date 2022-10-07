<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\MediaSyncService;
use App\Values\SyncResultCollection;
use Mockery\MockInterface;

class SettingTest extends TestCase
{
    private MediaSyncService|MockInterface $mediaSyncService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaSyncService = self::mock(MediaSyncService::class);
    }

    public function testSaveSettings(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $this->mediaSyncService->shouldReceive('sync')->once()
            ->andReturn(SyncResultCollection::create());

        $this->putAs('/api/settings', ['media_path' => __DIR__], $admin)
            ->assertSuccessful();

        self::assertSame(__DIR__, Setting::get('media_path'));
    }

    public function testNonAdminCannotSaveSettings(): void
    {
        $this->putAs('/api/settings', ['media_path' => __DIR__])
            ->assertForbidden();
    }
}
