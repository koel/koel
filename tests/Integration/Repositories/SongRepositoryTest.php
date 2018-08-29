<?php

namespace Tests\Integration\Repositories;

use App\Models\Song;
use App\Repositories\SongRepository;
use App\Services\HelperService;
use Illuminate\Contracts\Auth\Guard;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SongRepositoryTest extends TestCase
{
    /**
     * @var HelperService
     */
    private $helperService;

    /**
     * @var Guard|MockInterface
     */
    private $auth;

    /**
     * @var SongRepository
     */
    private $songRepository;

    public function setUp()
    {
        parent::setUp();
        $this->auth = Mockery::mock(Guard::class);
        $this->helperService = new HelperService();
        $this->songRepository = new SongRepository($this->auth, $this->helperService);
    }

    public function testGetOneByPath(): void
    {
        /** @var Song $song */
        $song = factory(Song::class)->create(['path' => 'foo']);
        self::assertSame($song->id, $this->songRepository->getOneByPath('foo')->id);
    }
}
