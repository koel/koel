<?php

namespace Tests\Unit\Models;

use App\Models\Setting;
use Tests\TestCase;

class SettingTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Setting::class, new Setting());
    }
}
