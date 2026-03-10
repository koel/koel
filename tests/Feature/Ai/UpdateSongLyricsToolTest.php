<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\UpdateSongLyrics;
use App\Models\Song;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UpdateSongLyricsToolTest extends TestCase
{
    #[Test]
    public function updatesSongLyricsForCurrentlyPlayingSong(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create([
            'title' => 'Stairway to Heaven',
            'lyrics' => '',
        ]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(UpdateSongLyrics::class);

        $response = $tool->handle(new Request([
            'lyrics' => "There's a lady who's sure all that glitters is gold",
        ]));

        $song->refresh();
        self::assertStringContainsString('Stairway to Heaven', (string) $response);
        self::assertSame("There's a lady who's sure all that glitters is gold", $song->lyrics);
        self::assertSame('update_lyrics', $result->action);
        self::assertSame($song->lyrics, $result->data['lyrics']);
        self::assertTrue($result->data['song']->is($song));
    }

    #[Test]
    public function overwritesExistingLyrics(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create([
            'title' => 'Hotel California',
            'lyrics' => 'Old lyrics here',
        ]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(UpdateSongLyrics::class);

        $tool->handle(new Request([
            'lyrics' => 'On a dark desert highway',
        ]));

        $song->refresh();
        self::assertSame('On a dark desert highway', $song->lyrics);
    }

    #[Test]
    public function returnsErrorWhenSongNotFound(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateSongLyrics::class);

        $response = $tool->handle(new Request([
            'lyrics' => 'Some lyrics',
        ]));

        self::assertStringContainsString('Could not find', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongSpecifiedAndNoneIsPlaying(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(UpdateSongLyrics::class);

        $response = $tool->handle(new Request([
            'lyrics' => 'Some lyrics',
            'query' => 'zzzznonexistentsongxyz',
        ]));

        self::assertStringContainsString('Could not find', (string) $response);
    }
}
