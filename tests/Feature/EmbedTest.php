<?php

namespace Tests\Feature;

use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Models\Embed;
use App\Models\Favorite;
use App\Models\Song;
use App\Values\EmbedOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;

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

    #[Test]
    public function resolveForOwnFavorites(): void
    {
        $user = create_user();

        $this
            ->postAs(
                'api/embeds/resolve',
                [
                    'embeddable_id' => $user->public_id,
                    'embeddable_type' => 'favorites',
                ],
                $user,
            )
            ->assertSuccessful()
            ->assertJsonStructure(EmbedResource::JSON_STRUCTURE)
            ->assertJsonPath('embeddable.id', $user->public_id);
    }

    #[Test]
    public function resolveFailsForAnotherUsersFavorites(): void
    {
        $this->postAs('api/embeds/resolve', [
            'embeddable_id' => create_user()->public_id,
            'embeddable_type' => 'favorites',
        ])->assertForbidden();
    }

    #[Test]
    public function getPayloadForFavoritesReturnsTheCurrentFavorites(): void
    {
        $user = create_user();
        Favorite::factory()->for($user)->createOne();

        $embedId = $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $user->public_id,
                'embeddable_type' => 'favorites',
            ],
            $user,
        )->json('id');

        $laterFavorite = Favorite::factory()->for($user)->createOne();
        Favorite::factory()->createOne();

        $options = EmbedOptions::make();

        $this
            ->getJson("api/embeds/$embedId/$options")
            ->assertSuccessful()
            ->assertJsonCount(2, 'embed.playables')
            ->assertJsonPath('embed.playables.1.id', $laterFavorite->favoriteable_id);
    }
}
