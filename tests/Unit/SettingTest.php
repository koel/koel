<?php

namespace Tests\Unit;

use App\Models\Setting;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Setting::class, new Setting());
    }

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
