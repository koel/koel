<?php

namespace Tests\Unit\Services;

use App\Services\iTunesService;
use Tests\TestCase;

class iTunesServiceTest extends TestCase
{
    public function testConfiguration()
    {
        config(['koel.itunes.enabled' => true]);
        /** @var iTunesService $iTunes */
        $iTunes = app()->make(iTunesService::class);
        $this->assertTrue($iTunes->used());

        config(['koel.itunes.enabled' => false]);
        $this->assertFalse($iTunes->used());
    }
}
