<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\MediaSyncService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery\MockInterface;

class SettingTest extends TestCase
{
    use WithoutMiddleware;

    /** @var MockInterface */
    private $mediaSyncService;

    public function setUp()
    {
        parent::setUp();
        $this->mediaSyncService = $this->mockIocDependency(MediaSyncService::class);
    }

    public function testSaveSettings()
    {
        $this->mediaSyncService->shouldReceive('sync')->once();

        $user = factory(User::class, 'admin')->create();
        $this->postAsUser('/api/settings', ['media_path' => __DIR__], $user)->seeStatusCode(200);

        $this->assertEquals(__DIR__, Setting::get('media_path'));
    }
}
