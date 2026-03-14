<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\AddToFavorites;
use App\Enums\FavoriteableType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class AddToFavoritesToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private AddToFavorites $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(AddToFavorites::class);
    }

    #[Test]
    public function favoritesCurrentlyPlayingSong(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Bohemian Rhapsody']);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $song->id));
        $this->tool = app()->make(AddToFavorites::class);

        $response = $this->tool->handle(new Request([]));

        self::assertSame('add_to_favorites', $this->result->action);
        self::assertSame(FavoriteableType::PLAYABLE, $this->result->data['type']);
        self::assertCount(1, $this->result->data['entities']);
        self::assertStringContainsString('Bohemian Rhapsody', (string) $response);
        self::assertTrue($song->favorites()->where('user_id', $this->user->id)->exists());
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $response = $this->tool->handle(new Request([]));

        self::assertNull($this->result->action);
        self::assertStringContainsString('Could not find', (string) $response);
    }

    #[Test]
    public function favoritesAlbumBySearch(): void
    {
        $album = Album::factory()->createOne(['name' => 'A Night at the Opera']);

        $albumRepository = Mockery::mock(AlbumRepository::class);
        $albumRepository
            ->shouldReceive('search')
            ->with('Night at the Opera', 1, $this->user)
            ->andReturn(new Collection([$album]));

        app()->instance(AlbumRepository::class, $albumRepository);
        $this->tool = app()->make(AddToFavorites::class);

        $response = $this->tool->handle(new Request(['type' => 'album', 'query' => 'Night at the Opera']));

        self::assertSame('add_to_favorites', $this->result->action);
        self::assertSame(FavoriteableType::ALBUM, $this->result->data['type']);
        self::assertStringContainsString('A Night at the Opera', (string) $response);
        self::assertTrue($album->favorites()->where('user_id', $this->user->id)->exists());
    }

    #[Test]
    public function favoritesArtistBySearch(): void
    {
        $artist = Artist::factory()->createOne(['name' => 'Queen']);

        $artistRepository = Mockery::mock(ArtistRepository::class);
        $artistRepository->shouldReceive('search')->with('Queen', 1, $this->user)->andReturn(new Collection([$artist]));

        app()->instance(ArtistRepository::class, $artistRepository);
        $this->tool = app()->make(AddToFavorites::class);

        $response = $this->tool->handle(new Request(['type' => 'artist', 'query' => 'Queen']));

        self::assertSame('add_to_favorites', $this->result->action);
        self::assertSame(FavoriteableType::ARTIST, $this->result->data['type']);
        self::assertStringContainsString('Queen', (string) $response);
        self::assertTrue($artist->favorites()->where('user_id', $this->user->id)->exists());
    }
}
