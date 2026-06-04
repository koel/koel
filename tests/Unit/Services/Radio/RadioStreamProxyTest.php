<?php

namespace Tests\Unit\Services\Radio;

use App\Services\Network\Network;
use App\Services\Radio\RadioStreamProxy;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RadioStreamProxyTest extends TestCase
{
    #[Test]
    public function openStreamRefusesUnsafeUrl(): void
    {
        $network = Mockery::mock(Network::class);
        $network->expects('isSafeUrl')->with('http://127.0.0.1/stream')->andReturnFalse();

        $proxy = new RadioStreamProxy($network);

        self::assertFalse($proxy->openStream('http://127.0.0.1/stream'));
    }
}
