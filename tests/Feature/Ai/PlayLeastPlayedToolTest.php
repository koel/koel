<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlayLeastPlayed;
use App\Models\Interaction;
use App\Models\Song;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlayLeastPlayedToolTest extends TestCase
{
    #[Test]
    public function playsLeastPlayedSongs(): void
    {
        $user = create_user();

        $neverPlayed = Song::factory()->for($user, 'owner')->create();

        $rarelyPlayed = Song::factory()->for($user, 'owner')->create();
        Interaction::factory()
            ->for($user)
            ->for($rarelyPlayed)
            ->create(['play_count' => 1]);

        $heavilyPlayed = Song::factory()->for($user, 'owner')->create();
        Interaction::factory()
            ->for($user)
            ->for($heavilyPlayed)
            ->create(['play_count' => 100]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(PlayLeastPlayed::class);
        $response = $tool->handle(new Request(['limit' => 2]));

        self::assertSame('play_songs', $result->action);
        self::assertCount(2, $result->data['songs']);

        $songIds = $result->data['songs']->pluck('id')->all();
        self::assertContains($neverPlayed->id, $songIds);
        self::assertContains($rarelyPlayed->id, $songIds);
        self::assertNotContains($heavilyPlayed->id, $songIds);
        self::assertStringContainsString('rarely or never played', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenLibraryIsEmpty(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(PlayLeastPlayed::class);
        $response = $tool->handle(new Request([]));

        self::assertNull($result->action);
        self::assertStringContainsString('No songs found', (string) $response);
    }

    #[Test]
    public function queuesInsteadOfPlaying(): void
    {
        $user = create_user();
        Song::factory()
            ->count(3)
            ->for($user, 'owner')
            ->create();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(PlayLeastPlayed::class);
        $response = $tool->handle(new Request(['queue' => true]));

        self::assertSame('play_songs', $result->action);
        self::assertTrue($result->data['queue']);
        self::assertStringContainsString('Added', (string) $response);
        self::assertStringContainsString('queue', (string) $response);
    }
}
