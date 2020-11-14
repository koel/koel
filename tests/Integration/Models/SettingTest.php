<?php

namespace Tests\Integration\Models;

use App\Models\Setting;
use Tests\TestCase;

class SettingTest extends TestCase
{
    public function testSetsKeyValuePair(): void
    {
        Setting::set('foo', 'bar');

        self::assertDatabaseHas('settings', [
            'key' => 'foo',
            'value' => serialize('bar'),
        ]);
    }

    public function testSupportAssociativeArray(): void
    {
        $settings = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        Setting::set($settings);

        self::assertDatabaseHas('settings', [
            'key' => 'foo',
            'value' => serialize('bar'),
        ])->assertDatabaseHas('settings', [
            'key' => 'baz',
            'value' => serialize('qux'),
        ]);
    }

    public function testUpdateSettings(): void
    {
        Setting::set('foo', 'bar');
        Setting::set('foo', 'baz');

        self::assertEquals('baz', Setting::get('foo'));
    }

    public function testGetSettings(): void
    {
        Setting::factory()->create([
            'key' => 'foo',
            'value' => 'bar',
        ]);

        self::assertSame('bar', Setting::get('foo'));
    }
}
