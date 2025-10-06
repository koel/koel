<?php

namespace Tests\Integration\Services;

use App\Models\Setting;
use App\Services\SettingService;
use App\Values\Branding;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    private SettingService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettingService::class);
    }

    #[Test]
    public function getBrandingForCommunityEdition(): void
    {
        $assert = function (): void {
            $branding = $this->service->getBranding();

            self::assertSame('Koel', $branding->name);
            self::assertNull($branding->logo);
            self::assertNull($branding->cover);
        };

        $assert();

        Setting::set('branding', Branding::make(
            name: 'Test Branding',
            logo: 'test-logo.png',
            cover: 'test-cover.png',
        ));

        $assert();
    }

    #[Test]
    public function updateMediaPath(): void
    {
        $this->service->updateMediaPath('/foo/bar/');

        self::assertSame('/foo/bar', Setting::get('media_path'));
    }
}
