<?php

namespace App\Services\Scanners\Strategies;

use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Support\Facades\File;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;
use Throwable;

// @mago-ignore lint:cyclomatic-complexity,kan-defect
class ParallelScanStrategy
{
    public function __construct(
        private readonly ScanResultDeserializer $deserializer,
    ) {}

    /** @param iterable<SplFileInfo> $files */
    public function scan(
        iterable $files,
        ScanConfiguration $config,
        int $jobs,
        ?callable $onProgress = null,
    ): ScanResultCollection {
        $paths = [];

        foreach ($files as $file) {
            $paths[] = $file->getRealPath();
        }

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
        $manifests = [];
        $processes = [];

        try {
            foreach ($chunks as $chunk) {
                $manifest = tempnam(sys_get_temp_dir(), 'koel_scan_') . '.json';
                File::put($manifest, json_encode($chunk));
                $manifests[] = $manifest;

                $process = new Process(self::buildCommand($manifest, $config), base_path());
                $process->setTimeout(config('koel.scan.timeout'));
                $process->start();
                $processes[] = $process;
            }

            return $this->collectResults($processes, $onProgress);
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

    /** @param Process[] $processes */
    private function collectResults(array $processes, ?callable $onProgress): ScanResultCollection
    {
        $results = ScanResultCollection::create();
        $buffers = array_fill(0, count($processes), '');
        $errBuffers = array_fill(0, count($processes), '');

        try {
            while ($processes) {
                foreach ($processes as $i => $process) {
                    $this->drainStdout($process, $buffers[$i], $results, $onProgress);
                    $this->drainStderr($process, $errBuffers[$i]);

                    if (!$process->isRunning()) {
                        $this->finalizeProcess($process, $buffers[$i], $errBuffers[$i], $results, $onProgress);
                        unset($processes[$i], $buffers[$i], $errBuffers[$i]);
                    }
                }

                if ($processes) {
                    usleep(50_000); // 50ms
                }
            }
        } catch (Throwable $e) {
            $this->terminateAll($processes);
            throw $e;
        }

        return $results;
    }

    /** @param Process[] $processes */
    private function terminateAll(array $processes): void
    {
        foreach ($processes as $process) {
            try {
                if (!$process->isRunning()) {
                    continue;
                }

                $process->stop(1);

                // @mago-ignore lint:no-empty-catch-clause
            } catch (Throwable) {
            }
        }
    }

    private function drainStdout(
        Process $process,
        string &$buffer,
        ScanResultCollection $results,
        ?callable $onProgress,
    ): void {
        $output = $process->getIncrementalOutput();

        if ($output === '') {
            return;
        }

        $buffer .= $output;

        while (($newlinePos = strpos($buffer, "\n")) !== false) {
            $line = substr($buffer, 0, $newlinePos);
            $buffer = substr($buffer, $newlinePos + 1);

            $this->addResult($this->deserializer->deserialize($line), $results, $onProgress);
        }
    }

    private function drainStderr(Process $process, string &$errBuffer): void
    {
        $errOutput = $process->getIncrementalErrorOutput();

        if ($errOutput !== '') {
            $errBuffer .= $errOutput;
        }
    }

    private function finalizeProcess(
        Process $process,
        string $buffer,
        string $errBuffer,
        ScanResultCollection $results,
        ?callable $onProgress,
    ): void {
        if (trim($buffer) !== '') {
            $this->addResult($this->deserializer->deserialize($buffer), $results, $onProgress);
        }

        if (!$process->isSuccessful()) {
            $stderr = trim($errBuffer);
            $msg = $stderr ?: 'Process exited with code ' . ($process->getExitCode() ?? 'unknown');

            throw new RuntimeException("Parallel scan worker failed: $msg");
        }
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
