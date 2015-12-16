<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class SettingTest extends TestCase
{
    use WithoutMiddleware, DatabaseTransactions;

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

    public function testSetMediaPath()
    {
        $user = factory(User::class, 'admin')->create();

        $this->expectsEvents(App\Events\MediaPathChanged::class);

        $this->actingAs($user)
            ->post('api/settings', [
                'media_path' => $this->mediaPath . '/',
            ])
            ->assertResponseOk();

        $this->assertEquals($this->mediaPath, Setting::get('media_path'));
    }
}
