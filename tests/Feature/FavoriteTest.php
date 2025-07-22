<?php

namespace Tests\Feature;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongFavoriteToggled;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\Song;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FavoriteTest extends TestCase
{
    #[Test]
    public function favorite(): void
    {
        Event::fake(SongFavoriteToggled::class);

        /** @var Song $song */
        $song = Song::factory()->create();
        $user = create_user();

        $this->postAs('api/favorites/toggle', [
            'type' => 'playable',
            'id' =>  $song->id,
        ], $user)
            ->assertJsonStructure(FavoriteResource::JSON_STRUCTURE);

        $this->assertDatabaseHas(Favorite::class, [
            'favoriteable_type' => 'playable',
            'favoriteable_id' => $song->id,
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(SongFavoriteToggled::class);
    }

    #[Test]
    public function undoFavorite(): void
    {
        Event::fake(SongFavoriteToggled::class);

        /** @var Favorite $favorite */
        $favorite = Favorite::factory()->create();

        $this->postAs('api/favorites/toggle', [
            'type' => 'playable',
            'id' => $favorite->favoriteable_id,
        ], $favorite->user)
            ->assertNoContent();

        $this->assertDatabaseMissing(Favorite::class, [
            'id' => $favorite->id,
        ]);

        Event::assertDispatched(SongFavoriteToggled::class);
    }

    #[Test]
    public function batchFavorite(): void
    {
        Event::fake(MultipleSongsLiked::class);

        /** @var Collection<Song> $songs */
        $songs = Song::factory()->count(2)->create();
        $user = create_user();

        $this->postAs('api/favorites', [
            'type' => 'playable',
            'ids' => $songs->pluck('id')->toArray(),
        ], $user)
            ->assertNoContent();

        foreach ($songs as $song) {
            $this->assertDatabaseHas(Favorite::class, [
                'favoriteable_type' => 'playable',
                'favoriteable_id' => $song->id,
                'user_id' => $user->id,
            ]);
        }

        Event::assertDispatched(MultipleSongsLiked::class);
    }

    #[Test]
    public function batchUndoFavorite(): void
    {
        Event::fake(MultipleSongsUnliked::class);

        $user = create_user();

        /** @var Collection<Favorite> $favorites */
        $favorites = Favorite::factory()->for($user)->count(2)->create();

        $this->deleteAs('api/favorites', [
            'type' => 'playable',
            'ids' => $favorites->pluck('favoriteable_id')->toArray(),
        ], $user)
            ->assertNoContent();

        foreach ($favorites as $favorite) {
            $this->assertDatabaseMissing(Favorite::class, [
                'id' => $favorite->id,
            ]);
        }

        Event::assertDispatched(MultipleSongsUnliked::class);
    }
}
