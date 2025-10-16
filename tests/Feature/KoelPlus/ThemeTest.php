<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\ThemeResource;
use App\Models\Theme;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class ThemeTest extends PlusTestCase
{
    #[Test]
    public function listTheme(): void
    {
        $user = create_user();
        Theme::factory()->for($user)->create();

        $this->getAs('api/themes', $user)
            ->assertSuccessful()
            ->assertJsonStructure(['*' => ThemeResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function createTheme(): void
    {
        $user = create_user();
        self::assertCount(0, $user->themes);

        $this->postAs('api/themes', [
            'name' => 'Test Theme',
            'fg_color' => '#ffffff',
            'bg_color' => '#000000',
            'highlight_color' => '#ff0000',
            'bg_image' => minimal_base64_encoded_image(),
            'font_family' => 'system-ui',
            'font_size' => '16.5',
        ], $user)
            ->assertCreated()
            ->assertJsonStructure(ThemeResource::JSON_STRUCTURE);

        self::assertCount(1, $user->refresh()->themes);

        /** @var Theme $theme */
        $theme = $user->themes->first();

        self::assertSame('Test Theme', $theme->name);
        self::assertSame('#ffffff', $theme->properties->fgColor);
        self::assertSame('#000000', $theme->properties->bgColor);
        self::assertSame('#ff0000', $theme->properties->highlightColor);
        self::assertSame('system-ui', $theme->properties->fontFamily);
        self::assertSame(16.5, $theme->properties->fontSize);
    }

    #[Test]
    public function deleteTheme(): void
    {
        /** @var Theme $theme */
        $theme = Theme::factory()->create();

        $this->deleteAs("api/themes/{$theme->id}", [], $theme->user)
            ->assertNoContent();

        self::assertModelMissing($theme);
    }

    #[Test]
    public function deleteThemeUnauthorized(): void
    {
        /** @var Theme $theme */
        $theme = Theme::factory()->create();

        $this->deleteAs("api/themes/{$theme->id}", [], create_user())
            ->assertForbidden();

        self::assertModelExists($theme);
    }
}
