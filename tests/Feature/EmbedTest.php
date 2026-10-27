<?php

namespace Tests\Feature;

use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Models\Album;
use App\Models\Embed;
use App\Models\Song;
use App\Models\Transcode;
use App\Values\EmbedOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;

class EmbedTest extends TestCase
{
    #[Test]
    public function resolveForEmbeddable(): void
    {
        $song = Song::factory()->createOne();

        $this
            ->postAs('api/embeds/resolve', [
                'embeddable_id' => $song->id,
                'embeddable_type' => 'playable',
            ])
            ->assertSuccessful()
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
            'theme',
        ];
        $embed = Embed::factory()->createOne();
        $options = EmbedOptions::make();

        $this->getAs("api/embeds/{$embed->id}/$options")->assertSuccessful()->assertJsonStructure($jsonStructure);

        // getJson() instead of getAs() to make sure it passes without authentication
        $this->getJson("api/embeds/{$embed->id}/$options")->assertSuccessful()->assertJsonStructure($jsonStructure);
    }

    #[Test]
    public function getPayloadWithThePreviewLengthOfSongsThatHaveAPreview(): void
    {
        $album = Album::factory()->createOne();
        $songWithPreview = Song::factory()->for($album)->createOne(['length' => 200, 'track' => 1]);
        $songWithoutPreview = Song::factory()->for($album)->createOne(['track' => 2]);
        Transcode::factory()->asPreview()->for($songWithPreview)->createOne();
        $embed = Embed::factory()->for($album->user)->for($album, 'embeddable')->createOne();

        $response = $this->getJson("api/embeds/{$embed->id}/" . EmbedOptions::make())->assertSuccessful();

        $playables = collect($response->json('embed.playables'))->keyBy('id');

        self::assertSame(30, $playables->get($songWithPreview->id)['preview_length']);
        self::assertArrayNotHasKey('preview_length', $playables->get($songWithoutPreview->id));
    }

    #[Test]
    public function getPayloadThrowsNotFoundIfEmbeddableIsNotAvailableAnyMore(): void
    {
        $embed = Embed::factory()->createOne();
        $embed->embeddable->delete();

        $options = EmbedOptions::make();

        $this->getJson("api/embeds/{$embed->id}/$options")->assertNotFound();
    }

    #[Test]
    public function resolveReturnsNotFoundWhenEmbeddingDisabled(): void
    {
        config(['koel.embed.enabled' => false]);

        $song = Song::factory()->createOne();

        $this->postAs('api/embeds/resolve', [
            'embeddable_id' => $song->id,
            'embeddable_type' => 'playable',
        ])->assertNotFound();
    }

    #[Test]
    public function getPayloadReturnsNotFoundWhenEmbeddingDisabled(): void
    {
        config(['koel.embed.enabled' => false]);

        $embed = Embed::factory()->createOne();
        $options = EmbedOptions::make();

        $this->getJson("api/embeds/{$embed->id}/$options")->assertNotFound();
    }
}
