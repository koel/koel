<?php

namespace App\Services\Scanners\Strategies;

use App\Services\WorkerProcessPool;
use App\Values\Scanning\ScanConfiguration;
use RuntimeException;

class ParallelScanStrategy
{
    private const int WORKER_IDLE_TIMEOUT_SECONDS = 600;

    public function __construct(
        private readonly ScanResultDeserializer $deserializer,
        private readonly WorkerProcessPool $workerPool,
    ) {}

    public function scan(
        string $pathList,
        int $pathCount,
        ScanConfiguration $config,
        int $jobs,
        callable $onResult,
    ): void {
        if ($pathCount === 0) {
            return;
        }

        $pathsPerWorker = (int) ceil($pathCount / $jobs);
        $commands = [];

        for ($offset = 0; $offset < $pathCount; $offset += $pathsPerWorker) {
            $commands[] = self::buildCommand($pathList, $offset, $pathsPerWorker, $config);
        }

        $this->workerPool->run(
            commands: $commands,
            timeout: null,
            onOutputLine: function (string $line) use ($onResult): void {
                $result = $this->deserializer->deserialize($line);

                if ($result) {
                    $onResult($result);
                }
            },
            onWorkerFailure: static function (?int $exitCode, string $errorOutput, bool $timedOut): void {
                $exitReason = trim($errorOutput) ?: 'Process exited with code ' . ($exitCode ?? 'unknown');
                $msg = $timedOut
                    ? sprintf('Process produced no output for %d seconds', self::WORKER_IDLE_TIMEOUT_SECONDS)
                    : $exitReason;

                throw new RuntimeException("Parallel scan worker failed: $msg");
            },
            idleTimeout: self::WORKER_IDLE_TIMEOUT_SECONDS,
        );
    }

    /** @return array<string> */
    private static function buildCommand(string $pathList, int $offset, int $limit, ScanConfiguration $config): array
    {
        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'koel:scan:chunk',
            $pathList,
            '--offset=' . $offset,
            '--limit=' . $limit,
            '--owner=' . $config->owner->id,
            '--no-interaction',
            '--no-ansi',
        ];

        if ($config->makePublic) {
            $command[] = '--public';
        }

        foreach ($config->ignores as $ignore) {
            $command[] = '--ignore=' . $ignore;
        }

        if ($config->force) {
            $command[] = '--force';
        }

        return $command;
    }
}
