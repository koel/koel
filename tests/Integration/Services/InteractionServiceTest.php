<?php

namespace Tests\Integration\Services;

use App\Models\Interaction;
use App\Models\Play;
use App\Models\Song;
use App\Services\InteractionService;
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
        $interaction = Interaction::factory()->createOne();
        $currentCount = $interaction->play_count;
        $this->interactionService->increasePlayCount($interaction->song, $interaction->user);

        self::assertSame($currentCount + 1, $interaction->refresh()->play_count);
    }

    #[Test]
    public function logEachSongPlay(): void
    {
        $song = Song::factory()->createOne();
        $user = create_user();

        $this->interactionService->increasePlayCount($song, $user);
        $this->interactionService->increasePlayCount($song, $user);

        self::assertSame(2, Play::query()->whereBelongsTo($user)->whereBelongsTo($song)->count());
    }

    #[Test]
    public function doNotLogEpisodePlays(): void
    {
        $episode = Song::factory()->asEpisode()->createOne();

        $this->interactionService->increasePlayCount($episode, create_user());

        self::assertSame(0, Play::query()->count());
    }
}
