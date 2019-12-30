<?php

namespace Tests\Unit\Services;

use app\Models\Song;
use app\Services\TranscodingService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TranscodingServiceTest extends TestCase
{
    use WithoutMiddleware;

    /** @var TranscodingService|MockInterface */
    private $transcodingServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->transcodingServiceMock = Mockery::mock(TranscodingService::class);

        $this->app->instance(TranscodingService::class, $this->transcodingServiceMock);
    }

    public function testSongShouldBeTranscoded()
    {
        $song = new Song();
        $booleanType = true;

        $this->transcodingServiceMock
        ->shouldReceive('songShouldBeTranscoded')
        ->with($song)
        ->andReturn($booleanType)
        ->once();
;
        $this->assertIsBool($this->transcodingServiceMock->songShouldBeTranscoded($song));
    }

    public function tearDown(): void {
        Mockery::close();
    }
}
