<?php

namespace Tests\Integration\Models;

use App\Models\Setting;
use Tests\TestCase;

class SettingTest extends TestCase
{
    /** @test */
    public function it_sets_a_key_value_pair()
    {
        // Given a key-value pair
        $key = 'foo';
        $value = 'bar';

        // When I call the method to save the key-value
        Setting::set($key, $value);

        // Then I see the key and serialized value in the database
        $this->assertDatabaseHas('settings', [
            'key' => 'foo',
            'value' => serialize('bar'),
        ]);
    }

    /** @test */
    public function it_supports_associative_arrays_when_saving_settings()
    {
        // Given an associative array of multiple settings
        $settings = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        // When I call the method to save the settings
        Setting::set($settings);

        // Then I see all settings the database
        $this->assertDatabaseHas('settings', [
            'key' => 'foo',
            'value' => serialize('bar'),
        ])->assertDatabaseHas('settings', [
            'key' => 'baz',
            'value' => serialize('qux'),
        ]);
    }

    /** @test */
    public function existing_settings_should_be_updated()
    {
        Setting::set('foo', 'bar');
        Setting::set('foo', 'baz');

        $this->assertEquals('baz', Setting::get('foo'));
    }

    /** @test */
    public function it_gets_the_setting_value_in_an_unserialized_format()
    {
        // Given a setting in the database
        factory(Setting::class)->create([
            'key' => 'foo',
            'value' => 'bar',
        ]);

        // When I get the setting using the key
        $value = Setting::get('foo');

        // Then I receive the value in an unserialized format
        $this->assertSame('bar', $value);
    }
}
