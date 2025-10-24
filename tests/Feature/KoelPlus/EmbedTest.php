<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Http\Resources\ThemeResource;
use App\Models\Embed;
use App\Models\Theme;
use App\Values\EmbedOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class EmbedTest extends PlusTestCase
{
    #[Test]
    public function getPayloadWithCustomTheme(): void
    {
        /** @var Theme $theme */
        $theme = Theme::factory()->create();

        $jsonStructure = [
            'embed' => EmbedResource::JSON_PUBLIC_STRUCTURE,
            'options' => EmbedOptionsResource::JSON_STRUCTURE,
            'theme' => ThemeResource::JSON_STRUCTURE,
        ];

        /** @var Embed $embed */
        $embed = Embed::factory()->create();
        $options = EmbedOptions::make(theme: $theme->id);

        $this->getAs("api/embeds/{$embed->id}/$options")
            ->assertSuccessful()
            ->assertJsonStructure($jsonStructure);

        // getJson() instead of getAs() to make sure it passes without authentication
        $this->getJson("api/embeds/{$embed->id}/$options")
            ->assertSuccessful()
            ->assertJsonStructure($jsonStructure);
    }
}
