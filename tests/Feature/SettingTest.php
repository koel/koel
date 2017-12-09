<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Media;

class SettingTest extends TestCase
{
    use WithoutMiddleware;

    /** @test */
    public function application_setting_is_saved_properly()
    {
        Media::shouldReceive('sync')->once();

        $user = factory(User::class, 'admin')->create();
        $this->postAsUser('/api/settings', ['media_path' => __DIR__], $user)->seeStatusCode(200);

        $this->assertEquals(__DIR__, Setting::get('media_path'));
    }
}
