<?php

namespace Tests\Unit\Models;

use App\Events\SongLikeToggled;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Interaction::class, new Interaction());
    }

}
