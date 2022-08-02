<?php

namespace Tests\Feature\V6;

use Tests\Feature\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        putenv('X_API_VERSION=v6');

        parent::setUp();
    }
}
