<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\FindInLibrary;
use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class FindInLibraryToolTest extends PlusTestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
    }

    #[Test]
    public function listAlbumsReleasedInAYearRange(): void
    {
        $artist = Artist::factory()->for($this->user)->createOne(['name' => 'Radiohead']);
        Album::factory()->for($artist)->for($this->user)->createOne(['name' => 'OK Computer', 'year' => 1997]);
        Album::factory()->for($artist)->for($this->user)->createOne(['name' => 'Kid A', 'year' => 2000]);

        $response = (string) app()
            ->make(FindInLibrary::class)
            ->handle(new Request(['type' => 'albums', 'from_year' => 1997, 'to_year' => 1997]));

        self::assertStringContainsString('OK Computer by Radiohead (1997)', $response);
        self::assertStringNotContainsString('Kid A', $response);
    }

    #[Test]
    public function listAlbumsMatchingBothTheQueryAndTheYearRange(): void
    {
        $artist = Artist::factory()->for($this->user)->createOne(['name' => 'Radiohead']);
        $okComputer = Album::factory()
            ->for($artist)
            ->for($this->user)
            ->createOne([
                'name' => 'OK Computer',
                'year' => 1997,
            ]);
        $kidA = Album::factory()->for($artist)->for($this->user)->createOne(['name' => 'Kid A', 'year' => 2000]);

        $this
            ->mock(AlbumRepository::class)
            ->expects('search')
            ->with('radiohead', PHP_INT_MAX, $this->user)
            ->andReturn(Collection::make([$okComputer, $kidA]));

        $response = (string) app()
            ->make(FindInLibrary::class)
            ->handle(new Request(['type' => 'albums', 'query' => 'radiohead', 'from_year' => 1997]));

        self::assertSame("Found 1 result(s):\n- OK Computer by Radiohead (1997)", $response);
    }

    #[Test]
    public function sayThereMayBeMoreWhenTheResultLimitIsReached(): void
    {
        $artist = Artist::factory()->for($this->user)->createOne();
        Album::factory()->for($artist)->for($this->user)->count(30)->create(['year' => 1997]);

        $response = (string) app()
            ->make(FindInLibrary::class)
            ->handle(new Request(['type' => 'albums', 'from_year' => 1997]));

        self::assertStringContainsString('Found 25 result(s)', $response);
        self::assertStringEndsWith('Only the first 25 are listed; there may be more.', $response);
    }

    #[Test]
    public function listArtistsMatchingTheQuery(): void
    {
        $artist = Artist::factory()->for($this->user)->createOne(['name' => 'Björk']);

        $this
            ->mock(ArtistRepository::class)
            ->expects('search')
            ->with('bjork', 25, $this->user)
            ->andReturn(Collection::make([$artist]));

        $response = (string) app()
            ->make(FindInLibrary::class)
            ->handle(new Request(['type' => 'artists', 'query' => 'bjork']));

        self::assertSame("Found 1 result(s):\n- Björk", $response);
    }

    #[Test]
    public function reportNoMatches(): void
    {
        self::assertSame(
            'Nothing in the library matches.',
            (string) app()->make(FindInLibrary::class)->handle(new Request(['type' => 'albums'])),
        );
    }
}
