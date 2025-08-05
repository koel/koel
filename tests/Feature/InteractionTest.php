<?php

namespace Tests\Feature;

use App\Events\MultipleSongsLiked;
use App\Events\PlaybackStarted;
use App\Events\SongFavoriteToggled;
use App\Models\Favorite;
use App\Models\Interaction;
use App\Models\Song;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class InteractionTest extends TestCase
{
    #[Test]
    public function increasePlayCount(): void
    {
        Event::fake(PlaybackStarted::class);

        $user = create_user();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->postAs('api/interaction/play', ['song' => $song->id], $user);

        $this->assertDatabaseHas(Interaction::class, [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 1,
        ]);

        // Try again
        $this->postAs('api/interaction/play', ['song' => $song->id], $user);

        $this->assertDatabaseHas(Interaction::class, [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 2,
        ]);
    }

    #[Test]
    /** @deprecated Only for older client (e.g., mobile app) */
    public function toggleLike(): void
    {
        Event::fake(SongFavoriteToggled::class);

        $user = create_user();

        /** @var Song $song */
        $song = Song::factory()->create();

        // Toggle on
        $this->postAs('api/interaction/like', ['song' => $song->id], $user);

        $this->assertDatabaseHas(Favorite::class, [
            'user_id' => $user->id,
            'favoriteable_id' => $song->id,
            'favoriteable_type' => 'playable',
        ]);

        // Toggle off
        $this->postAs('api/interaction/like', ['song' => $song->id], $user)
            ->assertNoContent();

        $this->assertDatabaseMissing(Favorite::class, [
            'user_id' => $user->id,
            'favoriteable_id' => $song->id,
            'favoriteable_type' => 'playable',
        ]);

        Event::assertDispatched(SongFavoriteToggled::class);
    }

    #[Test]
    /** @deprecated Only for older client (e.g., mobile app) */
    public function toggleLikeBatch(): void
    {
        Event::fake(MultipleSongsLiked::class);

        $user = create_user();

        /** @var Collection<Song> $songs */
        $songs = Song::factory(2)->create();
        $songIds = $songs->modelKeys();

        $this->postAs('api/interaction/batch/like', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            $this->assertDatabaseHas(Favorite::class, [
                'user_id' => $user->id,
                'favoriteable_id' => $song->id,
                'favoriteable_type' => 'playable',
            ]);
        }

        Event::assertDispatched(MultipleSongsLiked::class);

        $this->postAs('api/interaction/batch/unlike', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            $this->assertDatabaseMissing(Favorite::class, [
                'user_id' => $user->id,
                'favoriteable_id' => $song->id,
                'favoriteable_type' => 'playable',
            ]);
        }

        Event::assertDispatched(MultipleSongsLiked::class);
    }
}
