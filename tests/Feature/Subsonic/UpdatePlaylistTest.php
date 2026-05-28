<?php

namespace Tests\Feature\Subsonic;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesOwnedPlaylists;
use Tests\TestCase;

use function Tests\create_user;

class UpdatePlaylistTest extends TestCase
{
    use CreatesOwnedPlaylists;

    #[Test]
    public function updatesNameAndComment(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user);

        $this
            ->getJson(
                "/rest/updatePlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&playlistId={$playlist->id}&name=Renamed&comment=New+notes",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $playlist->refresh();
        self::assertSame('Renamed', $playlist->name);
        self::assertSame('New notes', $playlist->description);
    }

    #[Test]
    public function songIdToAddAppendsSongs(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user);
        $songs = Song::factory()->count(2)->create(['owner_id' => $user->id]);
        $songParams = implode('&', array_map(static fn (Song $song) => 'songIdToAdd=' . $song->id, $songs->all()));

        $this->getJson(
            "/rest/updatePlaylist.view?apiKey={$user->subsonic_api_key}"
            . "&f=json&playlistId={$playlist->id}&{$songParams}",
        )->assertOk();

        self::assertEqualsCanonicalizing($songs->modelKeys(), $playlist->refresh()->playables->modelKeys());
    }

    #[Test]
    public function songIndexToRemoveDropsByIndex(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user);
        $songs = Song::factory()->count(3)->create(['owner_id' => $user->id]);
        $playlist->addPlayables($songs, $user);

        $this->getJson(
            "/rest/updatePlaylist.view?apiKey={$user->subsonic_api_key}"
            . "&f=json&playlistId={$playlist->id}&songIndexToRemove=1",
        )->assertOk();

        $remaining = $playlist->refresh()->playables->modelKeys();
        self::assertCount(2, $remaining);
        self::assertNotContains($songs[1]->id, $remaining);
    }

    #[Test]
    public function smartPlaylistRejectsContentMutation(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user, smart: true);
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this
            ->getJson(
                "/rest/updatePlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&playlistId={$playlist->id}&songIdToAdd={$song->id}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed');
    }

    #[Test]
    public function nonNumericSongIndexIsRejected(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user);
        $songs = Song::factory()->count(2)->create(['owner_id' => $user->id]);
        $playlist->addPlayables($songs, $user);

        $this
            ->getJson(
                "/rest/updatePlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&playlistId={$playlist->id}&songIndexToRemove=foo",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);

        self::assertCount(2, $playlist->refresh()->playables);
    }

    #[Test]
    public function missingPlaylistIdReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/updatePlaylist.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
