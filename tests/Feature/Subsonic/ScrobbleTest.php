<?php

namespace Tests\Feature\Subsonic;

use App\Models\Interaction;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ScrobbleTest extends TestCase
{
    #[Test]
    public function recordsPlayCountForEachSong(): void
    {
        $user = create_user();
        $songs = Song::factory()->count(2)->create(['owner_id' => $user->id]);
        $ids = implode('&', array_map(static fn (Song $song) => 'id=' . $song->id, $songs->all()));

        $this
            ->getJson("/rest/scrobble.view?apiKey={$user->subsonic_api_key}&f=json&{$ids}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        foreach ($songs as $song) {
            $interaction = Interaction::query()->where('user_id', $user->id)->where('song_id', $song->id)->first();

            self::assertNotNull($interaction);
            self::assertSame(1, $interaction->play_count);
        }
    }

    #[Test]
    public function submissionFalseSkipsPlayCount(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this->getJson(
            "/rest/scrobble.view?apiKey={$user->subsonic_api_key}" . "&f=json&id={$song->id}&submission=false",
        )->assertOk();

        self::assertNull(Interaction::query()->where('user_id', $user->id)->where('song_id', $song->id)->first());
    }

    #[Test]
    public function missingIdReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/scrobble.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
