<?php

namespace Tests\Unit\Models;

use App\Models\SongZipArchive;
use Tests\TestCase;

class SongZipArchiveTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $songZipArchive = new SongZipArchive();
        $this->assertInstanceOf(SongZipArchive::class, $songZipArchive);
        $this->assertInstanceOf(\ZipArchive::class, $songZipArchive->getArchive());
    }
}
