<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use Tests\Traits\InteractsWithIoc;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions, CreatesApplication, InteractsWithIoc;

    public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
    }

    protected function tearDown()
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        Mockery::close();
        parent::tearDown();
    }
}
