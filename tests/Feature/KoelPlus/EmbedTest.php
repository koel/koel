<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Http\Resources\ThemeResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Embed;
use App\Models\Favorite;
use App\Models\Song;
use App\Models\Theme;
use App\Models\User;
use App\Services\Playlist\PlaylistCollaborationService;
use App\Values\EmbedOptions;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_guest;
use function Tests\create_playlist;
use function Tests\create_user;

class EmbedTest extends PlusTestCase
{
    #[Test]
    public function getPayloadWithCustomTheme(): void
    {
        $embed = Embed::factory()->createOne();
        $theme = Theme::factory()->for($embed->user)->createOne();

        $jsonStructure = [
            'embed' => EmbedResource::JSON_PUBLIC_STRUCTURE,
            'options' => EmbedOptionsResource::JSON_STRUCTURE,
            'theme' => ThemeResource::JSON_STRUCTURE,
        ];
        $options = EmbedOptions::make(theme: $theme->id);

        $this->getAs("api/embeds/{$embed->id}/$options")->assertSuccessful()->assertJsonStructure($jsonStructure);

        // getJson() instead of getAs() to make sure it passes without authentication
        $this->getJson("api/embeds/{$embed->id}/$options")->assertSuccessful()->assertJsonStructure($jsonStructure);
    }

    #[Test]
    public function getPayloadIgnoresAnotherUsersTheme(): void
    {
        $embed = Embed::factory()->createOne();
        $theme = Theme::factory()->createOne();
        $options = EmbedOptions::make(theme: $theme->id);

        $this->getJson("api/embeds/{$embed->id}/$options")->assertSuccessful()->assertJsonPath('theme', null);
    }

    #[Test]
    public function embedAnAlbumSharedByAnotherUser(): void
    {
        $owner = create_user();
        $album = Album::factory()->for($owner)->createOne();

        Song::factory()
            ->for($album)
            ->for($album->artist)
            ->for($owner, 'owner')
            ->public()
            ->createOne();

        $viewer = create_user();
        $viewer->preferences->includePublicMedia = true;
        $viewer->save();

        $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $album->id,
                'embeddable_type' => 'album',
            ],
            $viewer,
        )->assertSuccessful();
    }

    #[Test]
    public function embedAnArtistSharedByAnotherUser(): void
    {
        $owner = create_user();
        $artist = Artist::factory()->for($owner)->createOne();

        Song::factory()->for($artist)->for($owner, 'owner')->public()->createOne();

        $viewer = create_user();
        $viewer->preferences->includePublicMedia = true;
        $viewer->save();

        $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $artist->id,
                'embeddable_type' => 'artist',
            ],
            $viewer,
        )->assertSuccessful();
    }

    /** @return array<string, array{string, Closure(User): ?string}> */
    public static function provideEmbeddablesSharedWithTheGuest(): array
    {
        return [
            'song' => ['playable', static fn (): string => Song::factory()->public()->createOne()->id],
            'album' => [
                'album',
                static fn (): string => Song::factory()->public()->createOne()->album->id,
            ],
            'artist' => [
                'artist',
                static fn (): string => Song::factory()->public()->createOne()->artist->id,
            ],
            'playlist' => [
                'playlist',
                static function (User $guest): string {
                    $playlist = create_playlist();
                    $playlist->addCollaborator($guest);

                    return $playlist->id;
                },
            ],
            'favorites' => ['favorites', static fn (): ?string => null],
        ];
    }

    /** @param Closure(User): ?string $createEmbeddable */
    #[Test]
    #[DataProvider('provideEmbeddablesSharedWithTheGuest')]
    public function guestCannotEmbed(string $embeddableType, Closure $createEmbeddable): void
    {
        $guest = create_guest();

        $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $createEmbeddable($guest),
                'embeddable_type' => $embeddableType,
            ],
            $guest,
        )->assertForbidden();
    }

    #[Test]
    public function cannotEmbedAnAlbumNotSharedWithTheUser(): void
    {
        $owner = create_user();
        $album = Album::factory()->for($owner)->createOne();

        Song::factory()
            ->for($album)
            ->for($album->artist)
            ->for($owner, 'owner')
            ->private()
            ->createOne();

        $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $album->id,
                'embeddable_type' => 'album',
            ],
            create_user(),
        )->assertForbidden();
    }

    #[Test]
    public function stopServingAnEmbedOnceItsCreatorLosesAccess(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $embed = Embed::factory()->for($collaborator)->embedding($playlist)->createOne();
        $options = EmbedOptions::make();

        $this->getJson("api/embeds/{$embed->id}/$options")->assertSuccessful();

        app(PlaylistCollaborationService::class)->removeCollaborator($playlist->refresh(), $collaborator);

        $this->getJson("api/embeds/{$embed->id}/$options")->assertNotFound();
    }

    #[Test]
    public function favoritesEmbedSkipsSongsTheOwnerCannotAccess(): void
    {
        $user = create_user();
        $accessibleSong = Song::factory()->for($user, 'owner')->createOne();
        $otherUsersPrivateSong = Song::factory()->private()->createOne();

        Favorite::factory()->for($user)->for($accessibleSong, 'favoriteable')->createOne();
        Favorite::factory()->for($user)->for($otherUsersPrivateSong, 'favoriteable')->createOne();

        $embed = Embed::factory()->for($user)->embeddingFavorites()->createOne();

        $options = EmbedOptions::make();

        $this
            ->getJson("api/embeds/{$embed->id}/$options")
            ->assertSuccessful()
            ->assertJsonCount(1, 'embed.playables')
            ->assertJsonPath('embed.playables.0.id', $accessibleSong->id);
    }
}
