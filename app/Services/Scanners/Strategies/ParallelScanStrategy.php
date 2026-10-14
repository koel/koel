<?php

namespace App\Services\Scanners\Strategies;

use App\Services\WorkerProcessPool;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ParallelScanStrategy
{
    private const int WORKER_IDLE_TIMEOUT_SECONDS = 600;

    public function __construct(
        private readonly ScanResultDeserializer $deserializer,
        private readonly WorkerProcessPool $workerPool,
    ) {}

    /** @param array<string> $paths */
    public function scan(
        array $paths,
        ScanConfiguration $config,
        int $jobs,
        ?callable $onProgress = null,
    ): ScanResultCollection {
        if (!$paths) {
            return ScanResultCollection::create();
        }

        $chunks = array_chunk($paths, (int) ceil(count($paths) / $jobs));

        return $this->spawnAndCollect($chunks, $config, $onProgress);
    }

    private function spawnAndCollect(
        array $chunks,
        ScanConfiguration $config,
        ?callable $onProgress,
    ): ScanResultCollection {
        $results = ScanResultCollection::create();
        $manifests = [];

        try {
            foreach ($chunks as $chunk) {
                $manifest = tempnam(sys_get_temp_dir(), 'koel_scan_');
                File::put($manifest, json_encode($chunk));
                $manifests[] = $manifest;
            }

            $this->workerPool->run(
                commands: array_map(static fn (string $manifest): array => self::buildCommand(
                    $manifest,
                    $config,
                ), $manifests),
                timeout: null,
                onOutputLine: fn (string $line) => $this->addResult(
                    $this->deserializer->deserialize($line),
                    $results,
                    $onProgress,
                ),
                onWorkerFailure: static function (?int $exitCode, string $errorOutput, bool $timedOut): void {
                    $exitReason = trim($errorOutput) ?: 'Process exited with code ' . ($exitCode ?? 'unknown');
                    $msg = $timedOut
                        ? sprintf('Process produced no output for %d seconds', self::WORKER_IDLE_TIMEOUT_SECONDS)
                        : $exitReason;

                    throw new RuntimeException("Parallel scan worker failed: $msg");
                },
                idleTimeout: self::WORKER_IDLE_TIMEOUT_SECONDS,
            );

            return $results;
        } finally {
            foreach ($manifests as $manifest) {
                File::delete($manifest);
            }
        }
    }

    /** @return array<string> */
    private static function buildCommand(string $manifest, ScanConfiguration $config): array
    {
        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'koel:scan:chunk',
            $manifest,
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

    private function addResult(?ScanResult $result, ScanResultCollection $results, ?callable $onProgress): void
    {
        if (!$result) {
            return;
        }

        $results->add($result);

        if ($onProgress) {
            $onProgress($result);
        }
    }
}
