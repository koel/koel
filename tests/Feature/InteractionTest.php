<?php

namespace Tests\Feature;

use App\Events\MultipleSongsLiked;
use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function Tests\create_user;

class InteractionTest extends TestCase
{
    public function testIncreasePlayCount(): void
    {
        $this->withoutEvents();

        $user = create_user();

        /** @var Song $song */
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

    public function testToggleLike(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        $user = create_user();

        /** @var Song $song */
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
    }

    public function testToggleLikeBatch(): void
    {
        $this->expectsEvents(MultipleSongsLiked::class);

        $user = create_user();

        /** @var Collection|array<Song> $songs */
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
    }
}
