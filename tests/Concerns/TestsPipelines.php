<?php

namespace Tests\Concerns;

use Mockery;
use Mockery\MockInterface;

trait TestsPipelines
{
    /** @return MockInterface&object { next: callable } */
    private static function createNextClosureMock(...$expectedArgs): MockInterface
    {
        return tap(Mockery::mock(), static fn (MockInterface $mock) => $mock->expects('next')->with(...$expectedArgs));
    }
}
