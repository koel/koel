<?php

use App\Events\SongLikeToggled;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class InteractionTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;

    public function setUp()
    {
        parent::setUp();

        $this->createSampleMediaSet();
    }

    public function testPlayCountRegister()
    {
        $this->withoutEvents();
        $user = factory(User::class)->create();

        $song = Song::orderBy('id')->first();
        $this->actingAs($user)
            ->post('api/interaction/play', ['song' => $song->id]);

        $this->seeInDatabase('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 1,
        ]);

        // Try again
        $this->actingAs($user)
            ->post('api/interaction/play', ['song' => $song->id]);

        $this->seeInDatabase('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'play_count' => 2,
        ]);
    }

    public function testLikeRegister()
    {
        $this->expectsEvents(SongLikeToggled::class);

        $user = factory(User::class)->create();

        $song = Song::orderBy('id')->first();
        $this->actingAs($user)
            ->post('api/interaction/like', ['song' => $song->id]);

        $this->seeInDatabase('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 1,
        ]);

        // Try again
        $this->actingAs($user)
            ->post('api/interaction/like', ['song' => $song->id]);

        $this->seeInDatabase('interactions', [
            'user_id' => $user->id,
            'song_id' => $song->id,
            'liked' => 0,
        ]);
    }

    public function testBatchLikeAndUnlike()
    {
        $this->expectsEvents(SongLikeToggled::class);

        $user = factory(User::class)->create();

        $songs = Song::orderBy('id')->take(2)->get();
        $songIds = array_pluck($songs->toArray(), 'id');

        $this->actingAs($user)
            ->post('api/interaction/batch/like', ['songs' => $songIds]);

        foreach ($songs as $song) {
            $this->seeInDatabase('interactions', [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 1,
            ]);
        }

        $this->actingAs($user)
            ->post('api/interaction/batch/unlike', ['songs' => $songIds]);

        foreach ($songs as $song) {
            $this->seeInDatabase('interactions', [
                'user_id' => $user->id,
                'song_id' => $song->id,
                'liked' => 0,
            ]);
        }
    }
}
