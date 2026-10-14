<?php

namespace Tests\Unit\Services;

use App\Services\WorkerProcessPool;
use PHPUnit\Framework\Attributes\RequiresFunction;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class WorkerProcessPoolTest extends TestCase
{
    private WorkerProcessPool $pool;

    public function setUp(): void
    {
        parent::setUp();

        $this->pool = new WorkerProcessPool();
    }

    #[Test]
    public function joinsLinesSplitAcrossReadsAndEmitsTheUnterminatedRemainder(): void
    {
        $outputLines = [];

        $this->pool->run(
            commands: [
                self::php('echo "first ha"; flush(); usleep(200000); echo "lf\n\n  \nsecond\r\nlast  ";'),
            ],
            timeout: 10,
            onOutputLine: static function (string $line) use (&$outputLines): void {
                $outputLines[] = $line;
            },
            onWorkerFailure: static fn () => self::fail('No worker should fail.'),
        );

        self::assertSame(['first half', 'second', 'last'], $outputLines);
    }

    #[Test]
    public function emitsErrorLinesSeparatelyFromOutputLines(): void
    {
        $outputLines = [];
        $errorLines = [];

        $this->pool->run(
            commands: [
                self::php('echo "out\n"; fwrite(STDERR, "warning\n\nfatal");'),
            ],
            timeout: 10,
            onOutputLine: static function (string $line) use (&$outputLines): void {
                $outputLines[] = $line;
            },
            onWorkerFailure: static fn () => self::fail('No worker should fail.'),
            onErrorLine: static function (string $line) use (&$errorLines): void {
                $errorLines[] = $line;
            },
        );

        self::assertSame(['out'], $outputLines);
        self::assertSame(['warning', 'fatal'], $errorLines);
    }

    #[Test]
    public function reportsAFailedWorkerAndKeepsCollectingFromTheOthers(): void
    {
        $outputLines = [];
        $failures = [];

        $this->pool->run(
            commands: [
                self::php('fwrite(STDERR, "boom\n"); exit(3);'),
                self::php('usleep(300000); echo "done\n";'),
            ],
            timeout: 10,
            onOutputLine: static function (string $line) use (&$outputLines): void {
                $outputLines[] = $line;
            },
            onWorkerFailure: static function (?int $exitCode, string $errorOutput, bool $timedOut) use (
                &$failures,
            ): void {
                $failures[] = [$exitCode, $errorOutput, $timedOut];
            },
        );

        self::assertSame([[3, "boom\n", false]], $failures);
        self::assertSame(['done'], $outputLines);
    }

    #[Test]
    public function reportsATimedOutWorkerAsFailedAndKeepsCollectingFromTheOthers(): void
    {
        $outputLines = [];
        $timedOutFlags = [];

        $this->pool->run(
            commands: [
                self::php('sleep(30);'),
                self::php('echo "done\n";'),
            ],
            timeout: 1,
            onOutputLine: static function (string $line) use (&$outputLines): void {
                $outputLines[] = $line;
            },
            onWorkerFailure: static function (?int $exitCode, string $errorOutput, bool $timedOut) use (
                &$timedOutFlags,
            ): void {
                $timedOutFlags[] = $timedOut;
            },
        );

        self::assertSame([true], $timedOutFlags);
        self::assertSame(['done'], $outputLines);
    }

    #[Test]
    public function stopsOnlyTheWorkersThatStayIdleLongerThanTheIdleTimeout(): void
    {
        $outputLines = [];
        $timedOutFlags = [];

        $this->pool->run(
            commands: [
                self::php('sleep(30);'),
                self::php('for ($i = 0; $i < 4; $i++) { usleep(500000); echo "$i\n"; flush(); }'),
            ],
            timeout: null,
            onOutputLine: static function (string $line) use (&$outputLines): void {
                $outputLines[] = $line;
            },
            onWorkerFailure: static function (?int $exitCode, string $errorOutput, bool $timedOut) use (
                &$timedOutFlags,
            ): void {
                $timedOutFlags[] = $timedOut;
            },
            idleTimeout: 1,
        );

        self::assertSame([true], $timedOutFlags);
        self::assertSame(['0', '1', '2', '3'], $outputLines);
    }

    #[Test]
    #[RequiresFunction('posix_kill')]
    public function stopsTheRemainingWorkersWhenTheFailurePolicyThrows(): void
    {
        $runningWorkerPid = null;

        $this->assertThrows(
            function () use (&$runningWorkerPid): void {
                $this->pool->run(
                    commands: [
                        self::php('usleep(500000); exit(1);'),
                        self::php('echo getmypid() . "\n"; flush(); sleep(30);'),
                    ],
                    timeout: 60,
                    onOutputLine: static function (string $line) use (&$runningWorkerPid): void {
                        $runningWorkerPid = (int) $line;
                    },
                    onWorkerFailure: static fn () => throw new RuntimeException('Worker failed'),
                );
            },
            RuntimeException::class,
            'Worker failed',
        );

        self::assertNotNull($runningWorkerPid);
        self::assertFalse(posix_kill($runningWorkerPid, 0));
    }

    /** @return array<string> */
    private static function php(string $code): array
    {
        return [PHP_BINARY, '-r', $code];
    }
}
