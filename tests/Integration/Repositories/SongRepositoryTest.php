<?php

namespace Tests\Integration\Repositories;

use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\HelperService;
use Tests\TestCase;

class SongRepositoryTest extends TestCase
{
    /**
     * @var HelperService
     */
    private $helperService;

    /**
     * @var SongRepository
     */
    private $songRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->helperService = new HelperService();
        $this->songRepository = new SongRepository($this->helperService);
    }

    public function testGetOneByPath(): void
    {
        /** @var Song $song */
        $song = factory(Song::class)->create(['path' => 'foo']);
        self::assertSame($song->id, $this->songRepository->getOneByPath('foo')->id);
    }
}
