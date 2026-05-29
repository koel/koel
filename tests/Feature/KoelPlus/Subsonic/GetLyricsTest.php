<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class GetLyricsTest extends PlusTestCase
{
    #[Test]
    public function otherUsersSongLyricsAreNotReturned(): void
    {
        $owner = create_user();
        $requester = create_user();

        $artist = Artist::factory()->createOne(['name' => 'Muse', 'user_id' => $owner->id]);
        Song::factory()->createOne([
            'title' => 'Hysteria',
            'artist_id' => $artist->id,
            'artist_name' => $artist->name,
            'lyrics' => 'Secret lyrics',
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $response = $this
            ->getJson(
                '/rest/getLyrics.view?'
                    . Arr::query([
                        'apiKey' => $requester->subsonic_api_key,
                        'f' => 'json',
                        'artist' => 'Muse',
                        'title' => 'Hysteria',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertSame([], $response->json('subsonic-response.lyrics'));
    }
}
