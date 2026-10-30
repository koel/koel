<?php

namespace Tests\Feature;

use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Models\Embed;
use App\Models\Favorite;
use App\Models\Song;
use App\Values\EmbedOptions;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** @return array<string, array{Closure(): array{Embed, Song}}> */
    public static function provideEmbedsWithTheirSong(): array
    {
        return [
            'playable' => [
                static function (): array {
                    $song = Song::factory()->createOne();

                    return [Embed::factory()->for($song->owner)->embedding($song)->createOne(), $song];
                },
            ],
            'album' => [
                static function (): array {
                    $song = Song::factory()->createOne();

                    return [Embed::factory()->for($song->owner)->embedding($song->album)->createOne(), $song];
                },
            ],
            'artist' => [
                static function (): array {
                    $song = Song::factory()->createOne();

                    return [Embed::factory()->for($song->owner)->embedding($song->artist)->createOne(), $song];
                },
            ],
            'playlist' => [
                static function (): array {
                    $playlist = create_playlist();
                    $song = Song::factory()->for($playlist->owner, 'owner')->createOne();
                    $playlist->addPlayables($song);

                    return [Embed::factory()->for($playlist->owner)->embedding($playlist)->createOne(), $song];
                },
            ],
        ];
    }

    /** @param Closure(): array{Embed, Song} $createEmbedWithSong */
    #[Test]
    #[DataProvider('provideEmbedsWithTheirSong')]
    public function getPayload(Closure $createEmbedWithSong): void
    {
        [$embed, $song] = $createEmbedWithSong();

        $jsonStructure = [
            'embed' => EmbedResource::JSON_PUBLIC_STRUCTURE,
            'options' => EmbedOptionsResource::JSON_STRUCTURE,
            'theme',
        ];
        $options = EmbedOptions::make();

        $this->getAs("api/embeds/{$embed->id}/$options")->assertSuccessful()->assertJsonStructure($jsonStructure);

        // getJson() instead of getAs() to make sure it passes without authentication
        $this
            ->getJson("api/embeds/{$embed->id}/$options")
            ->assertSuccessful()
            ->assertJsonStructure($jsonStructure)
            ->assertJsonCount(1, 'embed.playables')
            ->assertJsonPath('embed.playables.0.id', $song->id);
    }

    #[Test]
    public function getPayloadThrowsNotFoundIfEmbeddableIsNotAvailableAnyMore(): void
    {
        $song = Song::factory()->createOne();
        $embed = Embed::factory()->for($song->owner)->embedding($song)->createOne();
        $song->delete();

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
    public function resolveForFavorites(): void
    {
        $user = create_user();

        $embedId = $this
            ->postAs('api/embeds/resolve', ['embeddable_type' => 'favorites'], $user)
            ->assertSuccessful()
            ->assertJsonStructure(EmbedResource::JSON_STRUCTURE)
            ->assertJsonPath('embeddable_id', null)
            ->assertJsonPath('embeddable.owner_name', $user->name)
            ->json('id');

        $this->postAs('api/embeds/resolve', ['embeddable_type' => 'favorites'], $user)->assertJsonPath('id', $embedId);
    }

    #[Test]
    public function getPayloadForFavoritesReturnsTheCurrentFavorites(): void
    {
        $user = create_user();
        Favorite::factory()->for($user)->createOne(['position' => 0]);
        $embed = Embed::factory()->for($user)->embeddingFavorites()->createOne();

        $laterFavorite = Favorite::factory()->for($user)->createOne(['position' => 1]);
        Favorite::factory()->createOne();

        $options = EmbedOptions::make();

        $this
            ->getJson("api/embeds/{$embed->id}/$options")
            ->assertSuccessful()
            ->assertJsonCount(2, 'embed.playables')
            ->assertJsonPath('embed.playables.1.id', $laterFavorite->favoriteable_id);
    }
}
