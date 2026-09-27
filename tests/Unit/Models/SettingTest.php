<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\Setting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingTest extends TestCase
{
    #[Test]
    public function setKeyValuePair(): void
    {
        Setting::set('foo', 'bar');

        $this->assertDatabaseHas(Setting::class, [
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

        $this->assertDatabaseHas(Setting::class, [
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
        Setting::factory()->createOne([
            'key' => 'foo',
            'value' => 'bar',
        ]);

        self::assertSame('bar', Setting::get('foo'));
    }

    #[Test]
    public function keepAnOrganizationSettingApartFromTheInstallWideOne(): void
    {
        $organization = Organization::factory()->createOne();

        Setting::set('foo', 'install-wide');
        Setting::set('foo', 'organization', $organization);

        self::assertSame('install-wide', Setting::get('foo'));
        self::assertSame('organization', Setting::get('foo', $organization));
    }

    #[Test]
    public function keepEachOrganizationSettingToItself(): void
    {
        $organization = Organization::factory()->createOne();
        $otherOrganization = Organization::factory()->createOne();

        Setting::set('foo', 'bar', $organization);

        self::assertNull(Setting::get('foo', $otherOrganization));
        self::assertNull(Setting::get('foo'));
    }
}
