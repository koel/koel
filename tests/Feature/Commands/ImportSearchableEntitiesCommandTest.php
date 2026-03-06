<?php

namespace Tests\Feature\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportSearchableEntitiesCommandTest extends TestCase
{
    #[Test]
    public function importAllSearchableEntities(): void
    {
        $this->artisan('koel:search:import')->assertSuccessful();
    }
}
