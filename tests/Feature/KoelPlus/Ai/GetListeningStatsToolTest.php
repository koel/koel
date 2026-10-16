<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\GetListeningStats;
use App\Models\Play;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class GetListeningStatsToolTest extends PlusTestCase
{
    private User $user;
    private GetListeningStats $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(GetListeningStats::class);
    }

    #[Test]
    public function listTheTopSongsOfThePeriod(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Only Shallow']);
        Play::factory()->for($this->user)->for($song)->count(2)->create();

        $response = (string) $this->tool->handle(new Request(['period' => 'week']));

        self::assertStringContainsString('2 play(s)', $response);
        self::assertStringContainsString("Only Shallow by {$song->artist_name} (2 play(s))", $response);
    }

    #[Test]
    public function reportAPeriodWithoutListening(): void
    {
        self::assertSame(
            'No listening history for this period.',
            (string) $this->tool->handle(new Request(['period' => 'year'])),
        );
    }
}
