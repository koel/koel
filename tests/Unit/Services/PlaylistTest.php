<?php

namespace Tests\Unit\Services;

use App\Models\Playlist;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Playlist::class, new Playlist());
    }
}
