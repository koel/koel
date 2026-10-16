<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\GetLibraryStats;
use App\Models\Genre;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class GetLibraryStatsToolTest extends PlusTestCase
{
    private User $user;
    private GetLibraryStats $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(GetLibraryStats::class);
    }

    #[Test]
    public function describeTheLibrarySizeAndGenres(): void
    {
        Genre::factory()->has(Song::factory()->for($this->user, 'owner')->count(3))->createOne(['name' => 'Shoegaze']);

        $response = (string) $this->tool->handle(new Request([]));

        self::assertStringContainsString('3 song(s)', $response);
        self::assertStringContainsString('Shoegaze: 3 song(s)', $response);
    }

    #[Test]
    public function reportAnEmptyLibrary(): void
    {
        self::assertSame('The library has no songs yet.', (string) $this->tool->handle(new Request([])));
    }
}
