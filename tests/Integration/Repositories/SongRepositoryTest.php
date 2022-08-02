<?php

namespace Tests\Integration\Repositories;

use App\Models\Song;
use App\Repositories\SongRepository;
use Tests\TestCase;

class SongRepositoryTest extends TestCase
{
    private SongRepository $songRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = app(SongRepository::class);
    }

    public function testGetOneByPath(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => 'foo']);
        self::assertSame($song->id, $this->songRepository->getOneByPath('foo')->id);
    }
}
