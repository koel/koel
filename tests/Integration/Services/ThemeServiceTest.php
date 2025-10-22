<?php

namespace Tests\Integration\Services;

use App\Models\Theme;
use App\Services\ThemeService;
use App\Values\Theme\ThemeCreateData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class ThemeServiceTest extends TestCase
{
    private ThemeService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(ThemeService::class);
    }

    #[Test]
    public function createTheme(): void
    {
        $user = create_user();

        $theme = $this->service->createTheme($user, ThemeCreateData::make(
            name: 'One Theme to Rule Them All',
            fgColor: '#ffffff',
            bgColor: '#000000',
            bgImage: minimal_base64_encoded_image(),
            highlightColor: '#ff0000',
            fontFamily: 'system-ui',
            fontSize: 14.0,
        ));

        self::assertSame('One Theme to Rule Them All', $theme->name);
        self::assertSame('#ffffff', $theme->properties->fgColor);
        self::assertSame('#000000', $theme->properties->bgColor);
        self::assertSame('#ff0000', $theme->properties->highlightColor);
        self::assertSame('system-ui', $theme->properties->fontFamily);
        self::assertSame(14.0, $theme->properties->fontSize);
        self::assertTrue($theme->user->is($user));
        self::assertNotEmpty($theme->properties->bgImage);
    }

    #[Test]
    public function createThemeWithoutABackgroundImage(): void
    {
        $user = create_user();

        $theme = $this->service->createTheme($user, ThemeCreateData::make(
            name: 'One Theme to Rule Them All',
            fgColor: '#ffffff',
            bgColor: '#000000',
            bgImage: '',
            highlightColor: '#ff0000',
            fontFamily: 'system-ui',
            fontSize: 14.0,
        ));

        self::assertSame('One Theme to Rule Them All', $theme->name);
        self::assertSame('#ffffff', $theme->properties->fgColor);
        self::assertSame('#000000', $theme->properties->bgColor);
        self::assertSame('#ff0000', $theme->properties->highlightColor);
        self::assertSame('system-ui', $theme->properties->fontFamily);
        self::assertSame(14.0, $theme->properties->fontSize);
        self::assertTrue($theme->user->is($user));
        self::assertEmpty($theme->properties->bgImage);
    }

    #[Test]
    public function deleteTheme(): void
    {
        /** @var Theme $theme */
        $theme = Theme::factory()->create();

        $this->service->deleteTheme($theme);

        self::assertModelMissing($theme);
    }
}
