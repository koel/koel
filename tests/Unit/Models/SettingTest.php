<?php

namespace Tests\Unit\Models;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingTest extends TestCase
{
    #[Test]
    public function setKeyValuePair(): void
    {
        Setting::set('foo', 'bar');

        self::assertDatabaseHas(Setting::class, [
            'key' => 'foo',
            'value' => json_encode('bar'),
        ]);
    }

    #[Test]
    public function supportAssociativeArray(): void
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

    #[Test]
    public function updateSettings(): void
    {
        Setting::set('foo', 'bar');
        Setting::set('foo', 'baz');

        self::assertSame('baz', Setting::get('foo'));
    }

    #[Test]
    public function getSettings(): void
    {
        Setting::factory()->create([
            'key' => 'foo',
            'value' => 'bar',
        ]);

        self::assertSame('bar', Setting::get('foo'));
    }
}
