<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use Tests\Traits\CreatesApplication;
use Tests\Traits\InteractsWithIoc;
use Tests\Traits\SandboxesTests;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;
    use CreatesApplication;
    use InteractsWithIoc;
    use SandboxesTests;

    public function setUp(): void
    {
        parent::setUp();
        $this->prepareForTests();
        self::createSandbox();
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
        self::destroySandbox();
        parent::tearDown();
    }
}
