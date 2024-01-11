<?php

namespace Tests\Integration\Services;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use App\Services\InteractionService;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function Tests\create_user;

class InteractionServiceTest extends TestCase
{
    private InteractionService $interactionService;

    public function setUp(): void
    {
        parent::setUp();

        $this->interactionService = new InteractionService();
    }

    public function testIncreasePlayCount(): void
    {
        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();
        $currentCount = $interaction->play_count;
        $this->interactionService->increasePlayCount($interaction->song, $interaction->user);

        self::assertSame($currentCount + 1, $interaction->refresh()->play_count);
    }

    public function testToggleLike(): void
    {
        $this->expectsEvents(SongLikeToggled::class);

        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();
        $currentLiked = $interaction->liked;

        $this->interactionService->toggleLike($interaction->song, $interaction->user);

        self::assertNotSame($currentLiked, $interaction->refresh()->liked);
    }

    public function testLikeMultipleSongs(): void
    {
        $this->expectsEvents(MultipleSongsLiked::class);

        /** @var Collection $songs */
        $songs = Song::factory(2)->create();
        $user = create_user();

        $this->interactionService->likeMany($songs, $user);

        $songs->each(static function (Song $song) use ($user): void {
            /** @var Interaction $interaction */
            $interaction = Interaction::query()
                ->where('song_id', $song->id)
                ->where('user_id', $user->id)
                ->first();

            self::assertTrue($interaction->liked);
        });
    }

    public function testUnlikeMultipleSongs(): void
    {
        $this->expectsEvents(MultipleSongsUnliked::class);
        $user = create_user();

        /** @var Collection $interactions */
        $interactions = Interaction::factory(3)->for($user)->create(['liked' => true]);

        $this->interactionService->unlikeMany($interactions->map(static fn (Interaction $i) => $i->song), $user);

        $interactions->each(static function (Interaction $interaction): void {
            self::assertFalse($interaction->refresh()->liked);
        });
    }
}
