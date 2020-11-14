<?php

namespace Tests\Integration\Services;

use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InteractionServiceTest extends TestCase
{
    private $interactionService;

    public function setUp(): void
    {
        parent::setUp();

        $this->interactionService = new InteractionService(new Interaction());
    }

    /** @test */
    public function it_increases_a_songs_play_count(): void
    {
        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();

        $this->interactionService->increasePlayCount($interaction->song, $interaction->user);

        $updatedInteraction = Interaction::find($interaction->id);
        self::assertEquals($interaction->play_count + 1, $updatedInteraction->play_count);
    }

    public function testToggleLike(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        $interaction = Interaction::factory()->create();

        $this->interactionService->toggleLike($interaction->song, $interaction->user);

        /** @var Interaction $interaction */
        $updatedInteraction = Interaction::find($interaction->id);
        self::assertNotSame($interaction->liked, $updatedInteraction->liked);
    }

    public function testLikeMultipleSongs(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        /** @var Collection $songs */
        $songs = Song::factory(2)->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->interactionService->batchLike($songs->pluck('id')->all(), $user);

        $songs->each(static function (Song $song) use ($user): void {
            self::assertTrue(Interaction::whereSongIdAndUserId($song->id, $user->id)->first()->liked);
        });
    }

    public function testUnlikeMultipleSongs(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        $user = User::factory()->create();

        /** @var Collection $interactions */
        $interactions = Interaction::factory(3)->create([
            'user_id' => $user->id,
            'liked' => true,
        ]);

        $this->interactionService->batchUnlike($interactions->pluck('song.id')->all(), $user);

        $interactions->each(static function (Interaction $interaction): void {
            self::assertFalse(Interaction::find($interaction->id)->liked);
        });
    }
}
