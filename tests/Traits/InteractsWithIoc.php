<?php

namespace Tests\Traits;

use Mockery;
use Mockery\MockInterface;

trait InteractsWithIoc
{
    /**
     * Mock an IOC dependency, for example an injected service in controllers.
     *
     * @param string $abstract
     * @param array  $args
     *
     * @return MockInterface
     */
    protected function mockIocDependency($abstract, ...$args)
    {
        return tap(Mockery::mock($abstract, ...$args), function ($mocked) use ($abstract) {
            app()->instance($abstract, $mocked);
        });
    }
}
