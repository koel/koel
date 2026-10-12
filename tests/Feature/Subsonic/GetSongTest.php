<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetSongTest extends TestCase
{
    #[Test]
    public function returnsSong(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['title' => 'Karma Police', 'owner_id' => $user->id]);

        $this
            ->getJson("/rest/getSong.view?apiKey={$user->subsonic_api_key}&f=json&id={$song->id}")
            ->assertOk()
            ->assertJsonStructure(['subsonic-response' => ['song' => SongResource::JSON_STRUCTURE]])
            ->assertJsonPath('subsonic-response.song.id', $song->id)
            ->assertJsonPath('subsonic-response.song.title', 'Karma Police');
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getSong.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    #[Test]
    public function exposesTheAlbumArtistOfACompilationSong(): void
    {
        $user = create_user();
        $variousArtists = Artist::factory()->for($user)->createOne(['name' => Artist::VARIOUS_NAME]);
        $compilation = Album::factory()->for($variousArtists)->for($user)->createOne();
        $song = Song::factory()
            ->for($compilation)
            ->for(Artist::factory()->for($user)->createOne(['name' => 'Radiohead']))
            ->createOne(['owner_id' => $user->id]);

        $this
            ->getJson("/rest/getSong.view?apiKey={$user->subsonic_api_key}&f=json&id={$song->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.song.displayAlbumArtist', Artist::VARIOUS_NAME)
            ->assertJsonPath('subsonic-response.song.albumArtists', [
                ['id' => $variousArtists->id, 'name' => Artist::VARIOUS_NAME],
            ]);
    }
}
