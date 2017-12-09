<?php

namespace Tests\Unit\Models;

use App\Models\Album;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Album::class, new Album());
    }
}
