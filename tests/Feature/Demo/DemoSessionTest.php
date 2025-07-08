<?php

namespace Tests\Feature\Demo;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoSessionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['koel.misc.demo' => true]);
        $this->followRedirects = true;
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        config(['koel.misc.demo' => false]);
        $this->followRedirects = false;

        parent::tearDown();
    }

    #[Test]
    public function dynamicallyCreateDemoAccount(): void
    {
        $this->mock(CrawlerDetect::class)
            ->expects('isCrawler')
            ->andReturnFalse();

        $demoAccount = $this->get('/')
            ->assertSee('window.DEMO_ACCOUNT')
            ->viewData('demo_account');

        self::assertStringEndsWith('@demo.koel.dev', $demoAccount['email']);
        self::assertEquals('demo', $demoAccount['password']);
    }

    #[Test]
    public function useFixedDemoAccountForBots(): void
    {
        $this->mock(CrawlerDetect::class)
            ->expects('isCrawler')
            ->andReturnTrue();

        $this->get('/')
            ->assertSee('window.DEMO_ACCOUNT')
            ->assertViewHas('demo_account', [
                'email' => 'demo@koel.dev',
                'password' => 'demo',
            ]);
    }
}
