<?php

namespace Tests\Feature;

use App\Events\SongLikeToggled;
use App\Events\SongsBatchLiked;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;

class InteractionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        static::createSampleMediaSet();
    }

    public function testIncreasePlayCount(): void
    {
        $this->withoutEvents();

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Song $song */
        $song = Song::query()->orderBy('id')->first();
        $this->postAs('api/interaction/play', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 1,
        ]);

        // Try again
        $this->postAs('api/interaction/play', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 2,
        ]);
    }

    public function testToggle(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Song $song */
        $song = Song::query()->orderBy('id')->first();
        $this->postAs('api/interaction/like', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 1,
        ]);

        // Try again
        $this->postAs('api/interaction/like', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 0,
        ]);
    }

    public function testToggleBatch(): void
    {
        $this->expectsEvents(SongsBatchLiked::class);

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Collection|array<Song> $songs */
        $songs = Song::query()->orderBy('id')->take(2)->get();
        $songIds = $songs->pluck('id')->all();

        $this->postAs('api/interaction/batch/like', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            self::assertDatabaseHas('interactions', [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 1,
            ]);
        }

        $this->postAs('api/interaction/batch/unlike', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            self::assertDatabaseHas('interactions', [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 0,
            ]);
        }
    }
}
