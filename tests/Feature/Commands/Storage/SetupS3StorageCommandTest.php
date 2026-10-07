<?php

namespace Tests\Feature\Commands\Storage;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetupS3StorageCommandTest extends TestCase
{
    #[Test]
    public function requiresKoelPlus(): void
    {
        $this->artisan('koel:storage:s3')->assertFailed();
    }
}
