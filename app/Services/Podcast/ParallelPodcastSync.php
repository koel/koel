<?php

namespace App\Services\Podcast;

use App\Services\WorkerProcessPool;
use Closure;

class ParallelPodcastSync
{
    private const int WORKER_TIMEOUT_SECONDS = 300;

    public function __construct(
        private readonly WorkerProcessPool $workerPool,
    ) {}

    /** @param Closure(object): void $onResult */
    public function execute(array $ids, int $jobs, Closure $onResult): void
    {
        $commands = array_map(
            static fn (array $chunk): array => [
                PHP_BINARY,
                base_path('artisan'),
                'koel:podcasts:sync-chunk',
                '--no-interaction',
                '--no-ansi',
                ...$chunk,
            ],
            array_chunk($ids, (int) ceil(count($ids) / $jobs)),
        );

        $this->workerPool->run(
            commands: $commands,
            timeout: self::WORKER_TIMEOUT_SECONDS,
            onOutputLine: static function (string $line) use ($onResult): void {
                $data = json_decode($line);

                if (is_object($data) && property_exists($data, 'status')) {
                    $onResult($data);
                }
            },
            onWorkerFailure: static fn (
                ?int $exitCode,
                string $errorOutput,
                bool $timedOut,
            ) => $onResult(self::makeErrorResult(
                $timedOut
                    ? sprintf('Worker timed out after %d seconds', self::WORKER_TIMEOUT_SECONDS)
                    : sprintf('Worker exited with code %d', $exitCode),
            )),
            onErrorLine: static fn (string $line) => $onResult(self::makeErrorResult($line)),
        );
    }

    private static function makeErrorResult(string $error): object
    {
        return (object) ['status' => 'error', 'title' => '', 'error' => $error];
    }
}
