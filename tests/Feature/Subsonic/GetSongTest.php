<?php

namespace Tests\Feature\Subsonic;

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
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.song.id', $song->id)
            ->assertJsonPath('subsonic-response.song.title', 'Karma Police')
            ->assertJsonPath('subsonic-response.song.type', 'music')
            ->assertJsonPath('subsonic-response.song.isDir', false);
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
}
