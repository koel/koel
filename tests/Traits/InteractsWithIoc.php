<?php

namespace Tests\Traits;

use Mockery;
use Mockery\MockInterface;

trait InteractsWithIoc
{
    /**
     * Mock an IOC dependency, for example an injected service in controllers.
     */
    protected static function mockIocDependency(string $abstract, ...$args): MockInterface
    {
        return tap(Mockery::mock($abstract, ...$args), static function (MockInterface $mocked) use ($abstract): void {
            app()->instance($abstract, $mocked);
        });
    }
}
