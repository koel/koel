<?php

namespace Tests\Integration\Models;

use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    /** @test */
    public function it_increases_a_songs_play_count()
    {
        // Given an interaction
        /** @var Interaction $interaction */
        $interaction = factory(Interaction::class)->create();

        // When I call the method to increases the song's play count
        Interaction::increasePlayCount($interaction->song, $interaction->user);

        // Then I see the play count is increased
        /** @var Interaction $interaction */
        $updatedInteraction = Interaction::find($interaction->id);
        $this->assertEquals($interaction->play_count + 1, $updatedInteraction->play_count);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_toggles_like_status()
    {
        $this->expectsEvents(SongLikeToggled::class);

        // Given an interaction
        $interaction = factory(Interaction::class)->create();

        // When I call the method to toggle the song's like status by user
        Interaction::toggleLike($interaction->song, $interaction->user);

        // Then I see the interaction's like status is toggled
        /** @var Interaction $interaction */
        $updatedInteraction = Interaction::find($interaction->id);
        $this->assertNotSame($interaction->liked, $updatedInteraction->liked);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function user_can_like_multiple_songs_at_once()
    {
        $this->expectsEvents(SongLikeToggled::class);

        // Given multiple song and a user
        /** @var Collection $songs */
        $songs = factory(Song::class, 2)->create();
        $user = factory(User::class)->create();

        // When I call the method to like songs in batch
        Interaction::batchLike($songs->pluck('id')->all(), $user);

        // Then I see the songs are all liked
        $songs->each(function (Song $song) use ($user) {
            $this->assertTrue(Interaction::whereSongIdAndUserId($song->id, $user->id)->first()->liked);
        });
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function user_can_unlike_multiple_songs_at_once()
    {
        $this->expectsEvents(SongLikeToggled::class);

        // Given multiple interaction records
        $user = factory(User::class)->create();
        /** @var Collection $interactions */
        $interactions = factory(Interaction::class, 3)->create([
            'user_id' => $user->id,
            'liked' => true,
        ]);

        // When I call the method to like songs in batch
        Interaction::batchUnlike($interactions->pluck('song.id')->all(), $user);

        // Then I see the songs are all liked
        $interactions->each(function (Interaction $interaction) {
            $this->assertFalse(Interaction::find($interaction->id)->liked);
        });
    }
}
