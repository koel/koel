<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeTest extends TestCase
{
    #[Test]
    public function renders(): void
    {
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertSee('window.ACCEPTED_AUDIO_EXTENSIONS')
            ->assertSee('window.BRANDING')
            ->assertSee('window.MAILER_CONFIGURED')
            ->assertSee('window.SSO_PROVIDERS');
    }
}
