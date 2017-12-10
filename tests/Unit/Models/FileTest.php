<?php

namespace Tests\Unit\Models;

use App\Models\File;
use SplFileInfo;
use Tests\TestCase;

class FileTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $file = new File(__DIR__.'/../songs/full.mp3');
        $this->assertInstanceOf(File::class, $file);
        $file = new File(new SplFileInfo(__DIR__.'/../songs/full.mp3'));
        $this->assertInstanceOf(File::class, $file);
    }
}
