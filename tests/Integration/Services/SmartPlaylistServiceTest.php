<?php

namespace Tests\Integration\Services;

use App\Services\SmartPlaylistService;
use Carbon\Carbon;
use Tests\TestCase;

class SmartPlaylistServiceTest extends TestCase
{
    private SmartPlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SmartPlaylistService::class);
        Carbon::setTestNow(new Carbon('2018-07-15'));
    }
}
