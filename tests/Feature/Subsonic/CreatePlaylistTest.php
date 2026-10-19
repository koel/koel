<?php

namespace Tests\Feature\Subsonic;

use App\Models\Favorite;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class CreatePlaylistTest extends TestCase
{
    #[Test]
    public function createsNewPlaylistWithSongs(): void
    {
        $user = create_user();
        $songs = Song::factory()->count(2)->create(['owner_id' => $user->id]);
        $songParams = implode('&', array_map(static fn (Song $song) => 'songId=' . $song->id, $songs->all()));

        $this
            ->getJson(
                "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&name=Subsonic+Mix&{$songParams}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.playlist.name', 'Subsonic Mix')
            ->assertJsonPath('subsonic-response.playlist.songCount', 2);

        self::assertSame(1, $user->ownedPlaylists()->where('name', 'Subsonic Mix')->count());
    }

    #[Test]
    public function entriesCarryFavoriteStatus(): void
    {
        $user = create_user();
        [$favorite, $other] = Song::factory()->count(2)->create(['owner_id' => $user->id])->all();
        Favorite::factory()->for($user)->for($favorite, 'favoriteable')->createOne();

        $response = $this
            ->getJson(
                "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&name=Starred+Mix&songId={$favorite->id}&songId={$other->id}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.playlist.entry.0.id', $favorite->id)
            ->assertJsonPath('subsonic-response.playlist.entry.1.id', $other->id);

        self::assertNotNull($response->json('subsonic-response.playlist.entry.0.starred'));
        self::assertNull($response->json('subsonic-response.playlist.entry.1.starred'));
    }

    #[Test]
    public function replaceTheSongsOfAnExistingPlaylist(): void
    {
        $playlist = create_playlist();
        $user = $playlist->owner;
        $playlist->addPlayables(Song::factory()->for($user, 'owner')->createMany(2));
        [$first, $second] = Song::factory()->for($user, 'owner')->createMany(2)->all();

        $this
            ->getJson(
                "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&playlistId={$playlist->id}&songId={$second->id}&songId={$first->id}",
            )
            ->assertJsonPath('subsonic-response.playlist.entry.0.id', $second->id)
            ->assertJsonPath('subsonic-response.playlist.entry.1.id', $first->id)
            ->assertJsonPath('subsonic-response.playlist.songCount', 2);
    }

    #[Test]
    public function refuseToReplaceTheSongsOfSomeoneElsesPlaylist(): void
    {
        $playlist = create_playlist();
        $user = create_user();

        $this->getJson(
            "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}&f=json&playlistId={$playlist->id}",
        )->assertJsonPath('subsonic-response.error.code', 50);
    }

    #[Test]
    public function missingNameReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
