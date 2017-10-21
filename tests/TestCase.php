<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions, CreatesApplication;

    public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
    }
}
