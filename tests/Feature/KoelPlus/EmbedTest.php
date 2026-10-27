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
use App\Services\Playlist\PlaylistCollaborationService;
use App\Values\EmbedOptions;
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

        $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $album->id,
                'embeddable_type' => 'album',
            ],
            create_guest(),
        )->assertSuccessful();
    }

    #[Test]
    public function embedAnArtistSharedByAnotherUser(): void
    {
        $owner = create_user();
        $artist = Artist::factory()->for($owner)->createOne();

        Song::factory()->for($artist)->for($owner, 'owner')->public()->createOne();

        $this->postAs(
            'api/embeds/resolve',
            [
                'embeddable_id' => $artist->id,
                'embeddable_type' => 'artist',
            ],
            create_guest(),
        )->assertSuccessful();
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
