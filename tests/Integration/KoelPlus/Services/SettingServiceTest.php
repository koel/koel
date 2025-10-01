<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Models\Setting;
use App\Services\SettingService;
use App\Values\Branding;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class SettingServiceTest extends PlusTestCase
{
    private SettingService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettingService::class);
    }

    #[Test]
    public function getBrandingForPlusEdition(): void
    {
        $branding = $this->service->getBranding();

        self::assertSame('Koel', $branding->name);
        self::assertNull($branding->logo);
        self::assertNull($branding->cover);

        Setting::set('branding', Branding::make(
            name: 'Test Branding',
            logo: 'test-logo.png',
            cover: 'test-cover.png',
        ));

        $branding = $this->service->getBranding();

        self::assertSame('Test Branding', $branding->name);
        self::assertSame(image_storage_url('test-logo.png'), $branding->logo);
        self::assertSame(image_storage_url('test-cover.png'), $branding->cover);
    }
}
