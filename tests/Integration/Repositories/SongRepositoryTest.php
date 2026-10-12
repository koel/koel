<?php

namespace Tests\Integration\Repositories;

use App\Models\Song;
use App\Repositories\SongRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongRepositoryTest extends TestCase
{
    private SongRepository $songRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = app(SongRepository::class);
    }

    #[Test]
    public function getOneByPath(): void
    {
        $song = Song::factory()->createOne(['path' => 'foo']);
        self::assertSame($song->id, $this->songRepository->findOneByPath('foo')->id);
    }

    #[Test]
    public function getOneAccessibleWithoutRelations(): void
    {
        $song = Song::factory()->createOne();

        $foundSong = $this->songRepository->getOneAccessibleWithoutRelations($song->id, $song->owner);

        self::assertTrue($foundSong->is($song));
        self::assertSame([], $foundSong->getRelations());
    }
}
