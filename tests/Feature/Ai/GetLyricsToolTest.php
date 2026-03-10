<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\GetLyrics;
use App\Models\Song;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetLyricsToolTest extends TestCase
{
    #[Test]
    public function getsLyricsOfCurrentlyPlayingSong(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create([
            'title' => 'Bohemian Rhapsody',
            'lyrics' => "Is this the real life?\nIs this just fantasy?",
        ]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(GetLyrics::class);
        $response = $tool->handle(new Request([]));

        self::assertSame('show_lyrics', $result->action);
        self::assertStringContainsString('Is this the real life?', $result->data['lyrics']);
        self::assertStringContainsString('Bohemian Rhapsody', (string) $response);
    }

    #[Test]
    public function returnsMessageWhenNoLyricsAvailable(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create([
            'title' => 'Instrumental Track',
            'lyrics' => '',
        ]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(GetLyrics::class);
        $response = $tool->handle(new Request([]));

        self::assertNull($result->action);
        self::assertStringContainsString('No lyrics available', (string) $response);
        self::assertStringContainsString('Instrumental Track', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(GetLyrics::class);
        $response = $tool->handle(new Request([]));

        self::assertNull($result->action);
        self::assertStringContainsString('Could not find', (string) $response);
    }
}
