<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\InteractsWithIoc;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions, CreatesApplication, InteractsWithIoc;

    public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
    }
}
