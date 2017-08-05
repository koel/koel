<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Media;

class SettingTest extends TestCase
{
    use WithoutMiddleware;

    public function testSetSingleKeyValue()
    {
        Setting::set('foo', 'bar');

        $this->seeInDatabase('settings', ['key' => 'foo', 'value' => 's:3:"bar";']);
    }

    public function testSetMultipleKeyValue()
    {
        Setting::set([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->seeInDatabase('settings', ['key' => 'foo', 'value' => 's:3:"bar";']);
        $this->seeInDatabase('settings', ['key' => 'baz', 'value' => 's:3:"qux";']);
    }

    public function testExistingShouldBeUpdated()
    {
        Setting::set('foo', 'bar');
        Setting::set('foo', 'baz');

        $this->assertEquals('baz', Setting::get('foo'));
    }

    public function testGet()
    {
        Setting::set('foo', 'bar');
        Setting::set('bar', ['baz' => 'qux']);

        $this->assertEquals('bar', Setting::get('foo'));
        $this->assertEquals(['baz' => 'qux'], Setting::get('bar'));
    }

    public function testApplicationSetting()
    {
        Media::shouldReceive('sync')->once();

        $user = factory(User::class, 'admin')->create();
        $this->postAsUser('/api/settings', ['media_path' => __DIR__], $user)
            ->seeStatusCode(200);

        $this->assertEquals(__DIR__, Setting::get('media_path'));
    }
}
