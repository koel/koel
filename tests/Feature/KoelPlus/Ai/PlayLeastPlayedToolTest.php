<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlayLeastPlayed;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlayLeastPlayedToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private PlayLeastPlayed $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(PlayLeastPlayed::class);
    }

    #[Test]
    public function playsLeastPlayedSongs(): void
    {
        $neverPlayed = Song::factory()->for($this->user, 'owner')->createOne();

        $rarelyPlayed = Song::factory()->for($this->user, 'owner')->createOne();
        Interaction::factory()
            ->for($this->user)
            ->for($rarelyPlayed)
            ->createOne(['play_count' => 1]);

        $heavilyPlayed = Song::factory()->for($this->user, 'owner')->createOne();
        Interaction::factory()
            ->for($this->user)
            ->for($heavilyPlayed)
            ->createOne(['play_count' => 100]);

        $response = $this->tool->handle(new Request(['limit' => 2]));

        self::assertSame('play_songs', $this->result->action);
        self::assertCount(2, $this->result->data['songs']);

        $songIds = $this->result->data['songs']->pluck('id')->all();
        self::assertContains($neverPlayed->id, $songIds);
        self::assertContains($rarelyPlayed->id, $songIds);
        self::assertNotContains($heavilyPlayed->id, $songIds);
        self::assertStringContainsString('rarely or never played', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenLibraryIsEmpty(): void
    {
        $response = $this->tool->handle(new Request([]));

        self::assertNull($this->result->action);
        self::assertStringContainsString('No songs found', (string) $response);
    }

    #[Test]
    public function queuesInsteadOfPlaying(): void
    {
        Song::factory()
            ->count(3)
            ->for($this->user, 'owner')
            ->create();

        $response = $this->tool->handle(new Request(['queue' => true]));

        self::assertSame('play_songs', $this->result->action);
        self::assertTrue($this->result->data['queue']);
        self::assertStringContainsString('Added', (string) $response);
        self::assertStringContainsString('queue', (string) $response);
    }
}
