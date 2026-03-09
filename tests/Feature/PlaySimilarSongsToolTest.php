<?php

namespace Tests\Feature;

use App\Ai\AiAssistantResult;
use App\Ai\Tools\PlaySimilarSongs;
use App\Models\Song;
use App\Repositories\SongRepository;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlaySimilarSongsToolTest extends TestCase
{
    #[Test]
    public function findsSimilarSongsByArtist(): void
    {
        $user = create_user();

        $referenceSong = Song::factory()->for($user, 'owner')->create(['title' => 'One']);
        Song::factory()
            ->for($user, 'owner')
            ->for($referenceSong->artist)
            ->create(['title' => 'Two']);
        Song::factory()->for($user, 'owner')->create(['title' => 'Unrelated']);

        $result = new AiAssistantResult();
        $tool = new PlaySimilarSongs($user, $result, app(SongRepository::class), $referenceSong->id);

        $response = $tool->handle(new Request([]));

        self::assertSame('play_songs', $result->action);
        self::assertCount(1, $result->data['songs']);
        self::assertSame('Two', $result->data['songs']->first()->title);
        self::assertStringContainsString('One', (string) $response);
    }

    #[Test]
    public function findsSimilarSongsByGenre(): void
    {
        $user = create_user();

        $referenceSong = Song::factory()->for($user, 'owner')->create(['title' => 'Rock Song']);
        $referenceSong->syncGenres('Rock');

        $similarSong = Song::factory()->for($user, 'owner')->create(['title' => 'Another Rock Song']);
        $similarSong->syncGenres('Rock');

        Song::factory()->for($user, 'owner')->create(['title' => 'Jazz Song']);

        $result = new AiAssistantResult();
        $tool = new PlaySimilarSongs($user, $result, app(SongRepository::class), $referenceSong->id);

        $response = $tool->handle(new Request([]));

        self::assertSame('play_songs', $result->action);
        self::assertNotEmpty($result->data['songs']);
        self::assertTrue($result->data['songs']->contains('title', 'Another Rock Song'));
        self::assertFalse($result->data['songs']->contains('title', 'Jazz Song'));
    }

    #[Test]
    public function returnsNotFoundWhenNoSimilarSongs(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create(['title' => 'Lonely Song']);

        $result = new AiAssistantResult();
        $tool = new PlaySimilarSongs($user, $result, app(SongRepository::class), $song->id);

        $response = $tool->handle(new Request([]));

        self::assertNull($result->action);
        self::assertStringContainsString('No similar songs', (string) $response);
    }

    #[Test]
    public function usesCurrentlyPlayingSongWhenNoTitleSpecified(): void
    {
        $user = create_user();

        $referenceSong = Song::factory()->for($user, 'owner')->create(['title' => 'Master of Puppets']);
        Song::factory()
            ->for($user, 'owner')
            ->for($referenceSong->artist)
            ->create(['title' => 'Battery']);

        $result = new AiAssistantResult();
        $tool = new PlaySimilarSongs($user, $result, app(SongRepository::class), $referenceSong->id);

        $response = $tool->handle(new Request([]));

        self::assertSame('play_songs', $result->action);
        self::assertNotEmpty($result->data['songs']);
        self::assertStringContainsString('Master of Puppets', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongSpecifiedAndNoneIsPlaying(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        $tool = new PlaySimilarSongs($user, $result, app(SongRepository::class), null);

        $response = $tool->handle(new Request([]));

        self::assertNull($result->action);
        self::assertStringContainsString('Could not determine', (string) $response);
    }
}
