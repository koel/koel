<?php

namespace Tests\Integration\Services;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use App\Services\InteractionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function increasePlayCount(): void
    {
        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();
        $currentCount = $interaction->play_count;
        $this->interactionService->increasePlayCount($interaction->song, $interaction->user);

        self::assertSame($currentCount + 1, $interaction->refresh()->play_count);
    }

    #[Test]
    public function toggleLike(): void
    {
        Event::fake(SongLikeToggled::class);

        /** @var Interaction $interaction */
        $interaction = Interaction::factory()->create();
        $currentLiked = $interaction->liked;

        $this->interactionService->toggleLike($interaction->song, $interaction->user);

        self::assertNotSame($currentLiked, $interaction->refresh()->liked);
        Event::assertDispatched(SongLikeToggled::class);
    }

    #[Test]
    public function likeMultipleSongs(): void
    {
        Event::fake(MultipleSongsLiked::class);

        /** @var Collection $songs */
        $songs = Song::factory(2)->create();
        $user = create_user();

        $this->interactionService->likeMany($songs, $user);

        $songs->each(static function (Song $song) use ($user): void {
            /** @var Interaction $interaction */
            $interaction = Interaction::query()
                ->whereBelongsTo($song)
                ->whereBelongsTo($user)
                ->first();

            self::assertTrue($interaction->liked);
        });

        Event::assertDispatched(MultipleSongsLiked::class);
    }

    #[Test]
    public function unlikeMultipleSongs(): void
    {
        Event::fake(MultipleSongsUnliked::class);
        $user = create_user();

        $interactions = Interaction::factory(3)->for($user)->create(['liked' => true]);

        $this->interactionService->unlikeMany($interactions->map(static fn (Interaction $i) => $i->song), $user); // @phpstan-ignore-line

        $interactions->each(static function (Interaction $interaction): void {
            self::assertFalse($interaction->refresh()->liked);
        });

        Event::assertDispatched(MultipleSongsUnliked::class);
    }
}
