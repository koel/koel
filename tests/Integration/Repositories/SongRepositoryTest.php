<?php

namespace Tests\Integration\Repositories;

use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\Helper;
use Tests\TestCase;

class SongRepositoryTest extends TestCase
{
    private SongRepository $songRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = new SongRepository(new Helper());
    }

    public function testGetOneByPath(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => 'foo']);
        self::assertSame($song->id, $this->songRepository->getOneByPath('foo')->id);
    }
}
