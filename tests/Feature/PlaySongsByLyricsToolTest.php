<?php

namespace Tests\Feature;

use App\Ai\AiAssistantResult;
use App\Ai\Tools\PlaySongsByLyrics;
use App\Models\Song;
use App\Repositories\SongRepository;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlaySongsByLyricsToolTest extends TestCase
{
    #[Test]
    public function findsSongsByLyrics(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create([
            'title' => 'Bohemian Rhapsody',
            'lyrics' => "Is this the real life\nIs this just fantasy\nCaught in a landslide\nNo escape from reality",
        ]);

        Song::factory()->for($user, 'owner')->create([
            'title' => 'Another Song',
            'lyrics' => 'Completely different lyrics here',
        ]);

        $result = new AiAssistantResult();
        $tool = new PlaySongsByLyrics($user, $result, app(SongRepository::class));

        $response = $tool->handle(new Request(['lyrics' => 'real life']));

        self::assertSame('play_songs', $result->action);
        self::assertNotEmpty($result->data['songs']);
        self::assertStringContainsString('Bohemian Rhapsody', (string) $response);
    }

    #[Test]
    public function returnsNotFoundMessageWhenNoLyricsMatch(): void
    {
        $user = create_user();
        Song::factory()->for($user, 'owner')->create([
            'lyrics' => 'Some known lyrics',
        ]);

        $result = new AiAssistantResult();
        $tool = new PlaySongsByLyrics($user, $result, app(SongRepository::class));

        $response = $tool->handle(new Request(['lyrics' => 'zzzznonexistentgibberishxyz']));

        self::assertNull($result->action);
        self::assertStringContainsString('No songs found', (string) $response);
    }

    #[Test]
    public function matchesPartialLyrics(): void
    {
        $user = create_user();
        Song::factory()->for($user, 'owner')->create([
            'title' => 'Stairway to Heaven',
            'lyrics' => "There's a lady who's sure all that glitters is gold\nAnd she's buying a stairway to heaven",
        ]);

        $result = new AiAssistantResult();
        $tool = new PlaySongsByLyrics($user, $result, app(SongRepository::class));

        $response = $tool->handle(new Request(['lyrics' => 'glitters is gold']));

        self::assertSame('play_songs', $result->action);
        self::assertNotEmpty($result->data['songs']);
        self::assertStringContainsString('Stairway to Heaven', (string) $response);
    }
}
