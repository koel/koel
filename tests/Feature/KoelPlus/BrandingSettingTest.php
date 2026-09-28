<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class BrandingSettingTest extends PlusTestCase
{
    #[Test]
    public function updateBrandingFromDefault(): void
    {
        $this->putAs(
            'api/settings/branding',
            [
                'name' => 'Little Bird',
                'logo' => minimal_base64_encoded_image(),
                'cover' => minimal_base64_encoded_image(),
            ],
            create_admin(),
        )->assertNoContent();

        $branding = Setting::get('branding', Organization::default());

        self::assertSame('Little Bird', $branding['name']);
        self::assertTrue(Str::isUrl($branding['logo']));
        self::assertTrue(Str::isUrl($branding['cover']));
    }

    #[Test]
    public function updateBrandingWithNoLogoOrCoverChanges(): void
    {
        Setting::set(
            'branding',
            [
                'name' => 'Koel',
                'logo' => 'old-logo.png',
                'cover' => 'old-cover.png',
            ],
            Organization::default(),
        );

        $this->putAs(
            'api/settings/branding',
            [
                'name' => 'Little Bird',
                'logo' => image_storage_url('old-logo.png'),
                'cover' => image_storage_url('old-cover.png'),
            ],
            create_admin(),
        )->assertNoContent();

        $branding = Setting::get('branding', Organization::default());

        self::assertSame('Little Bird', $branding['name']);
        self::assertSame(image_storage_url('old-logo.png'), $branding['logo']);
        self::assertSame(image_storage_url('old-cover.png'), $branding['cover']);
    }

    #[Test]
    public function updateBrandingReplacingLogoAndCover(): void
    {
        Setting::set(
            'branding',
            [
                'name' => 'Koel',
                'logo' => 'old-logo.png',
                'cover' => 'old-cover.png',
            ],
            Organization::default(),
        );

        $this->putAs(
            'api/settings/branding',
            [
                'name' => 'Little Bird',
                'logo' => minimal_base64_encoded_image(),
                'cover' => minimal_base64_encoded_image(),
            ],
            create_admin(),
        )->assertNoContent();

        $branding = Setting::get('branding', Organization::default());

        self::assertSame('Little Bird', $branding['name']);
        self::assertTrue(Str::isUrl($branding['logo']));
        self::assertTrue(Str::isUrl($branding['cover']));
        self::assertNotSame(image_storage_url('old-logo.png'), $branding['logo']);
        self::assertNotSame(image_storage_url('old-cover.png'), $branding['cover']);
    }

    #[Test]
    public function rejectsLogoAndCoverUrlsOtherThanTheCurrentOnes(): void
    {
        Http::fake();

        Setting::set(
            'branding',
            [
                'name' => 'Koel',
                'logo' => 'old-logo.png',
                'cover' => 'old-cover.png',
            ],
            Organization::default(),
        );

        $this->putAs(
            'api/settings/branding',
            [
                'name' => 'Little Bird',
                'logo' => 'http://169.254.169.254/latest/meta-data/',
                'cover' => 'https://example.com/cover.png',
            ],
            create_admin(),
        )->assertJsonValidationErrors(['logo', 'cover']);

        Http::assertNothingSent();
        self::assertSame('Koel', Setting::get('branding', Organization::default())['name']);
    }

    #[Test]
    public function nonAdminCannotSetBranding(): void
    {
        $this->putAs(
            'api/settings/branding',
            [
                'name' => 'Little Bird',
                'logo' => minimal_base64_encoded_image(),
                'cover' => minimal_base64_encoded_image(),
            ],
            create_user(),
        )->assertForbidden();
    }

    #[Test]
    public function keepEachOrganizationsBrandingToItself(): void
    {
        Setting::set('branding', ['name' => 'Koel'], Organization::default());
        $otherAdmin = User::factory()->admin()->for(Organization::factory())->createOne();

        $this->putAs('api/settings/branding', ['name' => 'Little Bird'], $otherAdmin)->assertNoContent();

        self::assertSame('Little Bird', Setting::get('branding', $otherAdmin->organization)['name']);
        self::assertSame('Koel', Setting::get('branding', Organization::default())['name']);
    }
}
