<?php

namespace Tests\Integration\Services;

use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Services\InteractionService;
use Exception;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InteractionServiceTest extends TestCase
{
    /**
     * @var InteractionService
     */
    private $interactionService;

    public function setUp()
    {
        parent::setUp();

        $this->interactionService = new InteractionService(new Interaction());
    }

    /** @test */
    public function it_increases_a_songs_play_count()
    {
        /** @var Interaction $interaction */
        $interaction = factory(Interaction::class)->create();

        $this->interactionService->increasePlayCount($interaction->song, $interaction->user);

        /** @var Interaction $interaction */
        $updatedInteraction = Interaction::find($interaction->id);
        self::assertEquals($interaction->play_count + 1, $updatedInteraction->play_count);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function it_toggles_like_status()
    {
        $this->expectsEvents(SongLikeToggled::class);

        $interaction = factory(Interaction::class)->create();

        $this->interactionService->toggleLike($interaction->song, $interaction->user);

        /** @var Interaction $interaction */
        $updatedInteraction = Interaction::find($interaction->id);
        self::assertNotSame($interaction->liked, $updatedInteraction->liked);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function user_can_like_multiple_songs_at_once()
    {
        $this->expectsEvents(SongLikeToggled::class);

        /** @var Collection $songs */
        $songs = factory(Song::class, 2)->create();
        $user = factory(User::class)->create();

        $this->interactionService->batchLike($songs->pluck('id')->all(), $user);

        $songs->each(static function (Song $song) use ($user) {
            self::assertTrue(Interaction::whereSongIdAndUserId($song->id, $user->id)->first()->liked);
        });
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function user_can_unlike_multiple_songs_at_once()
    {
        $this->expectsEvents(SongLikeToggled::class);

        $user = factory(User::class)->create();
        /** @var Collection $interactions */
        $interactions = factory(Interaction::class, 3)->create([
            'user_id' => $user->id,
            'liked' => true,
        ]);

        $this->interactionService->batchUnlike($interactions->pluck('song.id')->all(), $user);

        $interactions->each(static function (Interaction $interaction) {
            self::assertFalse(Interaction::find($interaction->id)->liked);
        });
    }
}
