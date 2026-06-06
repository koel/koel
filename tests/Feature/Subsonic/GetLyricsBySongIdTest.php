<?php

namespace Tests\Feature\Subsonic;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetLyricsBySongIdTest extends TestCase
{
    #[Test]
    public function returnsStructuredLyrics(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne([
            'title' => 'Karma Police',
            'artist_name' => 'Radiohead',
            'lyrics' => "Line one\nLine two\nLine three",
            'owner_id' => $user->id,
        ]);

        $response = $this
            ->getJson("/rest/getLyricsBySongId.view?apiKey={$user->subsonic_api_key}&f=json&id={$song->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $structured = $response->json('subsonic-response.lyricsList.structuredLyrics.0');
        self::assertSame('Radiohead', $structured['displayArtist']);
        self::assertSame('Karma Police', $structured['displayTitle']);
        self::assertSame('und', $structured['lang']);
        self::assertSame(0, $structured['offset']);
        self::assertSame(false, $structured['synced']);
        self::assertSame(
            [['value' => 'Line one'], ['value' => 'Line two'], ['value' => 'Line three']],
            $structured['line'],
        );
    }

    #[Test]
    public function emptyLyricsReturnsEmptyList(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['lyrics' => '', 'owner_id' => $user->id]);

        $this
            ->getJson("/rest/getLyricsBySongId.view?apiKey={$user->subsonic_api_key}&f=json&id={$song->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.lyricsList', []);
    }
}
