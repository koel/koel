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

    #[Test]
    public function rendersReverbServerWhenBroadcastingThroughReverb(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'reverb-key',
            'broadcasting.connections.reverb.options.host' => 'ws.example.com',
            'broadcasting.connections.reverb.options.port' => '443',
            'broadcasting.connections.reverb.options.useTLS' => true,
        ]);

        $this
            ->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertSee('"reverb":{"app_key":"reverb-key","host":"ws.example.com","port":443,"tls":true}', false);
    }

    #[Test]
    public function rendersNoReverbServerWhenBroadcastingElsewhere(): void
    {
        config(['broadcasting.default' => 'null']);

        $this->withoutVite()->get('/')->assertOk()->assertSee('"reverb":null', false);
    }
}
