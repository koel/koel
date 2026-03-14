<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\UpdateSongLyrics;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class UpdateSongLyricsToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private UpdateSongLyrics $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(UpdateSongLyrics::class);
    }

    #[Test]
    public function updatesSongLyricsForCurrentlyPlayingSong(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne([
            'title' => 'Stairway to Heaven',
            'lyrics' => '',
        ]);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $song->id));
        $this->tool = app()->make(UpdateSongLyrics::class);

        $response = $this->tool->handle(new Request([
            'lyrics' => "There's a lady who's sure all that glitters is gold",
        ]));

        $song->refresh();
        self::assertStringContainsString('Stairway to Heaven', (string) $response);
        self::assertSame("There's a lady who's sure all that glitters is gold", $song->lyrics);
        self::assertSame('update_lyrics', $this->result->action);
        self::assertSame($song->lyrics, $this->result->data['lyrics']);
        self::assertTrue($this->result->data['song']->is($song));
    }

    #[Test]
    public function overwritesExistingLyrics(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne([
            'title' => 'Hotel California',
            'lyrics' => 'Old lyrics here',
        ]);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $song->id));
        $this->tool = app()->make(UpdateSongLyrics::class);

        $this->tool->handle(new Request([
            'lyrics' => 'On a dark desert highway',
        ]));

        $song->refresh();
        self::assertSame('On a dark desert highway', $song->lyrics);
    }

    #[Test]
    public function returnsErrorWhenSongNotFound(): void
    {
        $response = $this->tool->handle(new Request([
            'lyrics' => 'Some lyrics',
        ]));

        self::assertStringContainsString('Could not find', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongSpecifiedAndNoneIsPlaying(): void
    {
        $response = $this->tool->handle(new Request([
            'lyrics' => 'Some lyrics',
            'query' => 'zzzznonexistentsongxyz',
        ]));

        self::assertStringContainsString('Could not find', (string) $response);
    }
}
