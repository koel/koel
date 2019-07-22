<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use Tests\Traits\InteractsWithIoc;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions, CreatesApplication, InteractsWithIoc;

    public function setUp(): void
    {
        parent::setUp();
        $this->prepareForTests();
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        Mockery::close();
        parent::tearDown();
    }
}
