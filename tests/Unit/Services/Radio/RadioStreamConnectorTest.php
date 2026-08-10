<?php

namespace Tests\Unit\Services\Radio;

use App\Services\Network\Network;
use App\Services\Radio\RadioStreamConnector;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RadioStreamConnectorTest extends TestCase
{
    #[Test]
    public function connectRefusesUnsafeUrl(): void
    {
        $network = Mockery::mock(Network::class);
        $network->expects('resolveUrlToPublicIps')->with('http://127.0.0.1/stream')->andReturn([]);

        $connector = new RadioStreamConnector($network);

        self::assertFalse($connector->connect('http://127.0.0.1/stream'));
    }

    #[Test]
    public function connectsToTheValidatedAddressRatherThanResolvingAgain(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $port = (int) explode(':', stream_socket_get_name($server, false))[1];

        // The host resolves to the loopback listener at validation time and never again:
        // if connect() asked DNS a second time, "rebind.test" would not resolve at all.
        $network = Mockery::mock(Network::class);
        $network->expects('resolveUrlToPublicIps')->with("http://rebind.test:$port/stream")->andReturn(['127.0.0.1']);

        $connector = new RadioStreamConnector($network);

        $connector->connect("http://rebind.test:$port/stream");

        $connection = stream_socket_accept($server, 5);
        $request = fread($connection, 1024);

        fclose($connection);
        fclose($server);

        self::assertStringContainsString('GET /stream', $request);
        self::assertStringContainsString("Host: rebind.test:$port", $request);
    }
}
