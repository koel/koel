<?php

namespace Tests\Integration\Services;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongFavoriteToggled;
use App\Models\Album;
use App\Models\Favorite;
use App\Models\Song;
use App\Services\FavoriteService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FavoriteServiceTest extends TestCase
{
    private FavoriteService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(FavoriteService::class);
    }

    #[Test]
    public function toggleFavoriteToTrue(): void
    {
        Event::fake(SongFavoriteToggled::class);

        $user = create_user();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->service->toggleFavorite($song, $user);

        $this->assertDatabaseHas(Favorite::class, [
            'user_id' => $user->id,
            'favoriteable_type' => 'playable',
            'favoriteable_id' => $song->id,
        ]);

        Event::assertDispatched(SongFavoriteToggled::class);
    }

    #[Test]
    public function toggleFavoriteToFalse(): void
    {
        Event::fake(SongFavoriteToggled::class);

        $user = create_user();

        /** @var Favorite $favorite */
        $favorite = Favorite::factory()->for($user)->create();

        $this->service->toggleFavorite($favorite->favoriteable, $user);
        $this->assertDatabaseMissing(Favorite::class, ['id' => $favorite->id]);

        Event::assertDispatched(SongFavoriteToggled::class);
    }

    #[Test]
    public function toggleFavoriteAlbum(): void
    {
        Event::fake(SongFavoriteToggled::class);

        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();

        $this->service->toggleFavorite($album, $user);

        $this->assertDatabaseHas(Favorite::class, [
            'user_id' => $user->id,
            'favoriteable_type' => 'album',
            'favoriteable_id' => $album->id,
        ]);

        Event::assertNotDispatched(SongFavoriteToggled::class);
    }

    #[Test]
    public function batchFavorite(): void
    {
        Event::fake(MultipleSongsLiked::class);

        /** @var Collection<int, Song> $songs */
        $songs = Song::factory()->count(2)->create();
        $user = create_user();

        $this->service->batchFavorite($songs, $user); // @phpstan-ignore-line

        foreach ($songs as $song) {
            $this->assertDatabaseHas(Favorite::class, [
                'user_id' => $user->id,
                'favoriteable_type' => 'playable',
                'favoriteable_id' => $song->id,
            ]);
        }

        Event::assertDispatched(MultipleSongsLiked::class, static function (MultipleSongsLiked $event) use ($user) {
            return $event->songs->count() === 2 && $event->user->is($user);
        });
    }

    #[Test]
    public function batchUndoFavorite(): void
    {
        Event::fake(MultipleSongsUnliked::class);

        $user = create_user();

        /** @var Collection<int, Favorite> $favorites */
        $favorites = Favorite::factory()->for($user)->count(2)->create();

        $this->service->batchUndoFavorite(
            $favorites->map(static fn (Favorite $favorite) => $favorite->favoriteable),
            $user,
        );

        foreach ($favorites as $favorite) {
            $this->assertDatabaseMissing(Favorite::class, ['id' => $favorite->id]);
        }

        Event::assertDispatched(MultipleSongsUnliked::class, static function (MultipleSongsUnliked $event) use ($user) {
            return $event->songs->count() === 2 && $event->user->is($user);
        });
    }
}
