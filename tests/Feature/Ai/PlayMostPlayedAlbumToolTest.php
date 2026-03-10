<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlayMostPlayedAlbum;
use App\Models\Album;
use App\Models\Interaction;
use App\Models\Song;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlayMostPlayedAlbumToolTest extends TestCase
{
    #[Test]
    public function playsMostPlayedAlbum(): void
    {
        $user = create_user();
        $album = Album::factory()->create();
        $songs = Song::factory()
            ->count(3)
            ->for($album)
            ->for($user, 'owner')
            ->create();

        foreach ($songs as $song) {
            Interaction::factory()
                ->for($user)
                ->for($song)
                ->create(['play_count' => 10]);
        }

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(PlayMostPlayedAlbum::class);
        $response = $tool->handle(new Request([]));

        self::assertSame('play_songs', $result->action);
        self::assertCount(3, $result->data['songs']);
        self::assertStringContainsString($album->name, (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoPlayHistory(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(PlayMostPlayedAlbum::class);
        $response = $tool->handle(new Request([]));

        self::assertNull($result->action);
        self::assertStringContainsString('No play history', (string) $response);
    }

    #[Test]
    public function queuesInsteadOfPlaying(): void
    {
        $user = create_user();
        $album = Album::factory()->create();
        $songs = Song::factory()
            ->count(2)
            ->for($album)
            ->for($user, 'owner')
            ->create();

        foreach ($songs as $song) {
            Interaction::factory()
                ->for($user)
                ->for($song)
                ->create(['play_count' => 5]);
        }

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(PlayMostPlayedAlbum::class);
        $response = $tool->handle(new Request(['queue' => true]));

        self::assertSame('play_songs', $result->action);
        self::assertTrue($result->data['queue']);
        self::assertStringContainsString('Added', (string) $response);
        self::assertStringContainsString('queue', (string) $response);
    }
}
