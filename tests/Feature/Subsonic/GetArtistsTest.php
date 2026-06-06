<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetArtistsTest extends TestCase
{
    #[Test]
    public function bucketsArtistsByLetterAndStripsArticles(): void
    {
        $user = create_user();

        $abba = Artist::factory()->createOne(['name' => 'Abba', 'user_id' => $user->id]);
        $beatles = Artist::factory()->createOne(['name' => 'The Beatles', 'user_id' => $user->id]);
        $hundredGecs = Artist::factory()->createOne(['name' => '100 gecs', 'user_id' => $user->id]);

        Album::factory()->createOne(['artist_id' => $abba->id, 'artist_name' => $abba->name, 'user_id' => $user->id]);
        Album::factory()->createOne([
            'artist_id' => $beatles->id,
            'artist_name' => $beatles->name,
            'user_id' => $user->id,
        ]);
        Album::factory()->createOne([
            'artist_id' => $hundredGecs->id,
            'artist_name' => $hundredGecs->name,
            'user_id' => $user->id,
        ]);

        $response = $this
            ->getJson('/rest/getArtists.view?apiKey=' . $user->subsonic_api_key . '&f=json')
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.artists.ignoredArticles', 'The El La Los Las Le Les');

        $indexes = $response->json('subsonic-response.artists.index');
        $byLetter = collect($indexes)->keyBy('name');

        self::assertSame('Abba', $byLetter->get('A')['artist'][0]['name']);
        self::assertSame('The Beatles', $byLetter->get('B')['artist'][0]['name']);
        self::assertSame('100 gecs', $byLetter->get('#')['artist'][0]['name']);
    }

    #[Test]
    public function excludesArtistsWithoutAlbums(): void
    {
        $user = create_user();

        Artist::factory()->createOne(['name' => 'Lonely', 'user_id' => $user->id]);

        $response = $this->getJson('/rest/getArtists.view?apiKey=' . $user->subsonic_api_key . '&f=json')->assertOk();

        $names = collect($response->json('subsonic-response.artists.index') ?? [])
            ->flatMap(static fn (array $idx) => array_column($idx['artist'], 'name'));

        self::assertFalse($names->contains('Lonely'));
    }
}
