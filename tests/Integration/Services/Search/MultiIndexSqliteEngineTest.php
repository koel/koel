<?php

namespace Tests\Integration\Services\Search;

use App\Models\Artist;
use App\Models\Song;
use App\Repositories\SongRepository;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\sandbox_path;

class MultiIndexSqliteEngineTest extends TestCase
{
    private SongRepository $songRepository;

    public function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(sandbox_path('search-indexes'));

        config([
            'scout.driver' => 'tntsearch',
            'scout.tntsearch.storage' => sandbox_path('search-indexes'),
        ]);

        $this->songRepository = app(SongRepository::class);
    }

    #[Test]
    public function songsAreSearchableAfterAnotherIndexHasBeenWrittenTo(): void
    {
        $user = create_user();

        Artist::factory()->for($user)->createOne(['name' => 'Ambient']);
        $song = Song::factory()->for($user, 'owner')->createOne(['title' => 'Tony Anderson - The King']);

        $found = $this->songRepository->search('Tony Anderson', 10, $user);

        self::assertCount(1, $found);
        self::assertTrue($found->first()->is($song));
    }
}
