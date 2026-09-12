<?php

namespace Tests\Integration\Services\Radio;

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
        ['process' => $process, 'stdout' => $stdout, 'port' => $port] = self::startLoopbackResponder();

        try {
            $url = "http://rebind.test:$port/stream";

            $network = Mockery::mock(Network::class);
            $network->expects('resolveUrlToPublicIps')->with($url)->andReturn(['127.0.0.1']);

            $stream = (new RadioStreamConnector($network))->connect($url);

            self::assertIsResource($stream);
            fclose($stream);

            $request = stream_get_contents($stdout);

            self::assertStringContainsString('GET /stream', $request);
            self::assertStringContainsString("Host: rebind.test:$port", $request);
        } finally {
            fclose($stdout);
            proc_close($process);
        }
    }

    /**
     * Serve one HTTP response from a loopback listener in a separate process, then echo the
     * request it received. A listener in this process could only be accepted after connect()
     * returned, leaving fopen() to wait out its full timeout twice over.
     *
     * @return array{process: resource, stdout: resource, port: int}
     */
    private static function startLoopbackResponder(): array
    {
        $script = <<<'PHP'
            $server = stream_socket_server('tcp://127.0.0.1:0');
            echo explode(':', stream_socket_get_name($server, false))[1] . PHP_EOL;

            $connection = stream_socket_accept($server, 10);
            $request = fread($connection, 2048);

            fwrite($connection, "HTTP/1.0 200 OK\r\nContent-Type: audio/mpeg\r\n\r\n");
            fclose($connection);

            echo $request;
            PHP;

        $process = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w']], $pipes);
        $port = (int) trim(fgets($pipes[1]));

        return ['process' => $process, 'stdout' => $pipes[1], 'port' => $port];
    }
}
