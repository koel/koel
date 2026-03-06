<?php

namespace Tests\Feature\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TidyLibraryCommandTest extends TestCase
{
    #[Test]
    public function showDeprecationWarning(): void
    {
        $this
            ->artisan('koel:tidy')
            ->expectsOutput('koel:tidy has been renamed. Use koel:prune instead.')
            ->assertSuccessful();
    }
}
