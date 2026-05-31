<?php

namespace Tests\Feature\Subsonic;

use Tests\TestCase;

abstract class SubsonicTestCase extends TestCase
{
    protected SubsonicHarness $subsonic;

    public function setUp(): void
    {
        parent::setUp();

        $this->subsonic = new SubsonicHarness($this);
    }
}
