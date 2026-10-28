<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Http\Resources\ThemeResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Embed;
use App\Models\Song;
use App\Models\Theme;
use App\Models\User;
use App\Services\Playlist\PlaylistCollaborationService;
use App\Values\EmbedOptions;
use Closure;
use Illuminate\Database\Eloquent\Model;
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

    /** @return array<string, array{string, Closure(User): Model}> */
    public static function provideEmbeddablesSharedWithTheGuest(): array
    {
        return [
            'song' => ['playable', static fn (): Model => Song::factory()->public()->createOne()],
            'album' => [
                'album',
                static fn (): Model => Song::factory()->public()->createOne()->album,
            ],
            'artist' => [
                'artist',
                static fn (): Model => Song::factory()->public()->createOne()->artist,
            ],
            'playlist' => [
                'playlist',
                static function (User $guest): Model {
                    $playlist = create_playlist();
                    $playlist->addCollaborator($guest);

                    return $playlist;
                },
            ],
        ];
    }

    /** @param Closure(User): Model $createEmbeddable */
    #[Test]
    #[DataProvider('provideEmbeddablesSharedWithTheGuest')]
    public function guestCannotEmbed(string $embeddableType, Closure $createEmbeddable): void
    {
        $guest = create_guest();
        $embeddable = $createEmbeddable($guest);

        $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $embeddable->getKey(),
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
        $embed = Embed::factory()->for($collaborator)->for($playlist, 'embeddable')->createOne();
        $options = EmbedOptions::make();

        $this->getJson("api/embeds/{$embed->id}/$options")->assertSuccessful();

        app(PlaylistCollaborationService::class)->removeCollaborator($playlist->refresh(), $collaborator);

        $this->getJson("api/embeds/{$embed->id}/$options")->assertNotFound();
    }
}
