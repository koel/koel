<?php

namespace Tests\Feature;

use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Models\Embed;
use App\Models\Song;
use App\Values\EmbedOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;

class EmbedTest extends TestCase
{
    #[Test]
    public function resolveForEmbeddable(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $this->postAs('api/embeds/resolve', [
            'embeddable_id' => $song->id,
            'embeddable_type' => 'playable',
        ])->assertSuccessful()
            ->assertJsonStructure(EmbedResource::JSON_STRUCTURE);
    }

    #[Test]
    public function resolveFailsIfUserDoesntHaveAccessToEmbeddable(): void
    {
        $playlist = create_playlist();

        $this->postAs('api/embeds/resolve', [
            'embeddable_id' => $playlist->id,
            'embeddable_type' => 'playlist',
        ])->assertForbidden();
    }

    #[Test]
    public function getPayload(): void
    {
        $jsonStructure = [
            'embed' => EmbedResource::JSON_PUBLIC_STRUCTURE,
            'options' => EmbedOptionsResource::JSON_STRUCTURE,
        ];

        /** @var Embed $embed */
        $embed = Embed::factory()->create();
        $options = EmbedOptions::make();

        $this->getAs("api/embeds/{$embed->id}/$options")
            ->assertSuccessful()
            ->assertJsonStructure($jsonStructure);

        // getJson() instead of getAs() to make sure it passes without authentication
        $this->getJson("api/embeds/{$embed->id}/$options")
            ->assertSuccessful()
            ->assertJsonStructure($jsonStructure);
    }

    #[Test]
    public function getPayloadThrowsNotFoundIfEmbeddableIsNotAvailableAnyMore(): void
    {
        /** @var Embed $embed */
        $embed = Embed::factory()->create();
        $embed->embeddable->delete();

        $options = EmbedOptions::make();

        $this->getJson("api/embeds/{$embed->id}/$options")
            ->assertNotFound();
    }
}
