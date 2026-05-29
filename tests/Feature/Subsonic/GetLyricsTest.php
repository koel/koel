<?php

namespace Tests\Feature\Subsonic;

use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetLyricsTest extends TestCase
{
    #[Test]
    public function returnsLyricsForMatchingArtistAndTitle(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['name' => 'Muse', 'user_id' => $user->id]);
        Song::factory()->createOne([
            'title' => 'Hysteria',
            'artist_id' => $artist->id,
            'artist_name' => $artist->name,
            'lyrics' => "It's bugging me\nGrating me",
            'owner_id' => $user->id,
        ]);

        $this
            ->getJson(
                '/rest/getLyrics.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'artist' => 'Muse',
                        'title' => 'Hysteria',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.lyrics.artist', 'Muse')
            ->assertJsonPath('subsonic-response.lyrics.title', 'Hysteria')
            ->assertJsonPath('subsonic-response.lyrics.value', "It's bugging me\nGrating me");
    }

    #[Test]
    public function returnsEmptyLyricsWhenNoSongMatches(): void
    {
        $user = create_user();

        $response = $this
            ->getJson(
                '/rest/getLyrics.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                        'artist' => 'Nobody',
                        'title' => 'Nothing',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertSame([], $response->json('subsonic-response.lyrics'));
    }

    #[Test]
    public function returnsEmptyLyricsWhenSongHasNoLyrics(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['name' => 'Silent', 'user_id' => $user->id]);
        Song::factory()->createOne([
            'title' => 'Wordless',
            'artist_id' => $artist->id,
            'artist_name' => $artist->name,
            'lyrics' => '',
            'owner_id' => $user->id,
        ]);

        $response = $this->getJson(
            '/rest/getLyrics.view?'
                . Arr::query([
                    'apiKey' => $user->subsonic_api_key,
                    'f' => 'json',
                    'artist' => 'Silent',
                    'title' => 'Wordless',
                ]),
        )->assertOk();

        self::assertSame([], $response->json('subsonic-response.lyrics'));
    }
}
