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
        ['process' => $process, 'stdout' => $stdout, 'port' => $port] = self::startLoopbackResponder([200]);

        try {
            $url = "http://rebind.test:$port/stream";
            $stream = self::connectWithResolvedAddress($url);

            self::assertIsResource($stream);
            fclose($stream);

            [$request] = self::readRequests($stdout);

            self::assertStringContainsString('GET /stream', $request);
            self::assertStringContainsString("Host: rebind.test:$port", $request);
        } finally {
            self::stopResponder($process, $stdout);
        }
    }

    #[Test]
    public function retriesWithoutIcyMetadataWhenTheStationRejectsIt(): void
    {
        ['process' => $process, 'stdout' => $stdout, 'port' => $port] = self::startLoopbackResponder([400, 200]);

        try {
            $url = "http://rebind.test:$port/stream";
            $stream = self::connectWithResolvedAddress($url);

            self::assertIsResource($stream);
            fclose($stream);

            [$icyAttempt, $retry] = self::readRequests($stdout);

            self::assertStringContainsString('Icy-MetaData: 1', $icyAttempt);
            self::assertStringNotContainsString('Icy-MetaData', $retry);
            self::assertStringContainsString("Host: rebind.test:$port", $retry);
        } finally {
            self::stopResponder($process, $stdout);
        }
    }

    /** @return resource|false */
    private static function connectWithResolvedAddress(string $url): mixed
    {
        $network = Mockery::mock(Network::class);
        $network->expects('resolveUrlToPublicIps')->with($url)->andReturn(['127.0.0.1']);

        return (new RadioStreamConnector($network))->connect($url);
    }

    /**
     * Serve one response per status from a loopback listener in a separate process, then echo
     * the requests it received. A listener in this process could only be accepted after
     * connect() returned, leaving every fopen() attempt to wait out its full timeout.
     *
     * @param list<int> $statuses
     * @return array{process: resource, stdout: resource, port: int}
     */
    private static function startLoopbackResponder(array $statuses): array
    {
        $script = <<<'PHP'
            $server = stream_socket_server('tcp://127.0.0.1:0');
            echo explode(':', stream_socket_get_name($server, false))[1] . PHP_EOL;

            $requests = [];

            foreach (explode(',', $argv[1]) as $status) {
                $connection = stream_socket_accept($server, 10);
                $requests[] = fread($connection, 2048);

                fwrite($connection, "HTTP/1.0 $status Response\r\nContent-Type: audio/mpeg\r\n\r\n");
                fclose($connection);
            }

            echo implode("\f", $requests);
            PHP;

        $process = proc_open([PHP_BINARY, '-r', $script, implode(',', $statuses)], [1 => ['pipe', 'w']], $pipes);

        return ['process' => $process, 'stdout' => $pipes[1], 'port' => (int) trim(fgets($pipes[1]))];
    }

    /**
     * @param resource $stdout
     * @return list<string>
     */
    private static function readRequests(mixed $stdout): array
    {
        return explode("\f", stream_get_contents($stdout));
    }

    /**
     * Signal the responder before waiting on it: proc_close() blocks until the child exits, and
     * a failed assertion leaves it parked in stream_socket_accept() with requests still to serve.
     *
     * @param resource $process
     * @param resource $stdout
     */
    private static function stopResponder(mixed $process, mixed $stdout): void
    {
        proc_terminate($process);
        fclose($stdout);
        proc_close($process);
    }
}
