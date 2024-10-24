<?php

namespace Tests\Feature;

use App\Events\MultipleSongsLiked;
use App\Events\PlaybackStarted;
use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
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
        $song = Song::factory()->create();

        $this->postAs('api/interaction/play', ['song' => $song->id], $user);

        self::assertDatabaseHas(Interaction::class, [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 1,
        ]);

        // Try again
        $this->postAs('api/interaction/play', ['song' => $song->id], $user);

        self::assertDatabaseHas(Interaction::class, [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 2,
        ]);
    }

    #[Test]
    public function toggleLike(): void
    {
        Event::fake(SongLikeToggled::class);

        $user = create_user();
        $song = Song::factory()->create();

        $this->postAs('api/interaction/like', ['song' => $song->id], $user);

        self::assertDatabaseHas(Interaction::class, [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 1,
        ]);

        // Try again
        $this->postAs('api/interaction/like', ['song' => $song->id], $user);

        self::assertDatabaseHas(Interaction::class, [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 0,
        ]);

        Event::assertDispatched(SongLikeToggled::class);
    }

    #[Test]
    public function toggleLikeBatch(): void
    {
        Event::fake(MultipleSongsLiked::class);

        $user = create_user();
        $songs = Song::factory(2)->create();
        $songIds = $songs->pluck('id')->all();

        $this->postAs('api/interaction/batch/like', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            self::assertDatabaseHas(Interaction::class, [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 1,
            ]);
        }

        $this->postAs('api/interaction/batch/unlike', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            self::assertDatabaseHas(Interaction::class, [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 0,
            ]);
        }

        Event::assertDispatched(MultipleSongsLiked::class);
    }
}
