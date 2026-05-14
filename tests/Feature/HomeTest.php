<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeTest extends TestCase
{
    #[Test]
    public function renders(): void
    {
        $this
            ->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertSee('window.KOEL = ', false)
            ->assertSee('accepted_audio_extensions', false)
            ->assertSee('mailer_configured', false)
            ->assertSee('sso_providers', false)
            ->assertSee('branding', false);
    }
}
