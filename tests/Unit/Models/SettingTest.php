<?php

namespace Tests\Unit\Models;

use App\Models\Setting;
use Tests\TestCase;

class SettingTest extends TestCase
{
    public function testSetKeyValuePair(): void
    {
        Setting::set('foo', 'bar');

        self::assertDatabaseHas(Setting::class, [
            'key' => 'foo',
            'value' => json_encode('bar'),
        ]);
    }

    public function testSupportAssociativeArray(): void
    {
        $settings = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        Setting::set($settings);

        self::assertDatabaseHas(Setting::class, [
            'key' => 'foo',
            'value' => json_encode('bar'),
        ])->assertDatabaseHas(Setting::class, [
            'key' => 'baz',
            'value' => json_encode('qux'),
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
