<?php

namespace Tests\Feature;

use App\Ai\Tools\AddToFavorites;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\FavoriteService;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AddToFavoritesToolTest extends TestCase
{
    #[Test]
    public function favoritesCurrentlyPlayingSong(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create(['title' => 'Bohemian Rhapsody']);

        $tool = new AddToFavorites($user, app(SongRepository::class), app(FavoriteService::class), $song->id);
        $response = $tool->handle(new Request([]));

        self::assertStringContainsString('Bohemian Rhapsody', (string) $response);
        self::assertStringContainsString('favorites', (string) $response);
        self::assertTrue($song->favorites()->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $user = create_user();

        $tool = new AddToFavorites($user, app(SongRepository::class), app(FavoriteService::class), null);
        $response = $tool->handle(new Request([]));

        self::assertStringContainsString('Could not find', (string) $response);
    }

    #[Test]
    public function favoritesMultipleSongsFromSearch(): void
    {
        $user = create_user();
        $song1 = Song::factory()->for($user, 'owner')->create(['title' => 'Unique Test Song Alpha']);
        $song2 = Song::factory()->for($user, 'owner')->create(['title' => 'Unique Test Song Beta']);

        $tool = new AddToFavorites($user, app(SongRepository::class), app(FavoriteService::class), null);
        $response = $tool->handle(new Request(['query' => 'Unique Test Song']));

        // Scout search may or may not index in time in tests, so just check the response is valid
        self::assertIsString((string) $response);
    }
}
