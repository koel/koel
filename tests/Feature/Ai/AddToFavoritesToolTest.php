<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\AddToFavorites;
use App\Enums\FavoriteableType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;
use Mockery;
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

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(AddToFavorites::class);
        $response = $tool->handle(new Request([]));

        self::assertSame('add_to_favorites', $result->action);
        self::assertSame(FavoriteableType::PLAYABLE, $result->data['type']);
        self::assertCount(1, $result->data['entities']);
        self::assertStringContainsString('Bohemian Rhapsody', (string) $response);
        self::assertTrue($song->favorites()->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(AddToFavorites::class);
        $response = $tool->handle(new Request([]));

        self::assertNull($result->action);
        self::assertStringContainsString('Could not find', (string) $response);
    }

    #[Test]
    public function favoritesAlbumBySearch(): void
    {
        $user = create_user();
        $album = Album::factory()->create(['name' => 'A Night at the Opera']);

        $albumRepository = Mockery::mock(AlbumRepository::class);
        $albumRepository
            ->shouldReceive('search')
            ->with('Night at the Opera', 1, $user)
            ->andReturn(new Collection([$album]));

        app()->instance(AlbumRepository::class, $albumRepository);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(AddToFavorites::class);
        $response = $tool->handle(new Request(['type' => 'album', 'query' => 'Night at the Opera']));

        self::assertSame('add_to_favorites', $result->action);
        self::assertSame(FavoriteableType::ALBUM, $result->data['type']);
        self::assertStringContainsString('A Night at the Opera', (string) $response);
        self::assertTrue($album->favorites()->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function favoritesArtistBySearch(): void
    {
        $user = create_user();
        $artist = Artist::factory()->create(['name' => 'Queen']);

        $artistRepository = Mockery::mock(ArtistRepository::class);
        $artistRepository->shouldReceive('search')->with('Queen', 1, $user)->andReturn(new Collection([$artist]));

        app()->instance(ArtistRepository::class, $artistRepository);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(AddToFavorites::class);
        $response = $tool->handle(new Request(['type' => 'artist', 'query' => 'Queen']));

        self::assertSame('add_to_favorites', $result->action);
        self::assertSame(FavoriteableType::ARTIST, $result->data['type']);
        self::assertStringContainsString('Queen', (string) $response);
        self::assertTrue($artist->favorites()->where('user_id', $user->id)->exists());
    }
}
