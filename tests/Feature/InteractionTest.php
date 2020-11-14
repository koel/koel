<?php

namespace Tests\Feature;

use App\Events\SongLikeToggled;
use App\Models\Song;
use App\Models\User;
use Exception;

class InteractionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        static::createSampleMediaSet();
    }

    /** @test */
    public function play_count_is_increased(): void
    {
        $this->withoutEvents();
        $user = User::factory()->create();

        $song = Song::orderBy('id')->first();
        $this->postAsUser('api/interaction/play', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 1,
        ]);

        // Try again
        $this->postAsUser('api/interaction/play', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 2,
        ]);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function user_can_like_and_unlike_a_song(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        $user = User::factory()->create();

        $song = Song::orderBy('id')->first();
        $this->postAsUser('api/interaction/like', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 1,
        ]);

        // Try again
        $this->postAsUser('api/interaction/like', ['song' => $song->id], $user);

        self::assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 0,
        ]);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function user_can_like_and_unlike_songs_in_batch(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        $user = User::factory()->create();

        $songs = Song::orderBy('id')->take(2)->get();
        $songIds = array_pluck($songs->toArray(), 'id');

        $this->postAsUser('api/interaction/batch/like', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            self::assertDatabaseHas('interactions', [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 1,
            ]);
        }

        $this->postAsUser('api/interaction/batch/unlike', ['songs' => $songIds], $user);

        foreach ($songs as $song) {
            self::assertDatabaseHas('interactions', [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 0,
            ]);
        }
    }
}
