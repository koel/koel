<?php

namespace App\Services\Scanners\Strategies;

use App\Enums\ScanResultType;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ParallelScanStrategy
{
    /** @param iterable<\SplFileInfo> $files */
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
        $manifests = [];
        $processes = [];

        try {
            foreach ($chunks as $chunk) {
                $manifest = tempnam(sys_get_temp_dir(), 'koel_scan_') . '.json';
                File::put($manifest, json_encode($chunk));
                $manifests[] = $manifest;

                $process = new Process($this->buildCommand($manifest, $config), base_path());
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
    private function buildCommand(string $manifest, ScanConfiguration $config): array
    {
        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'koel:scan:chunk',
            $manifest,
            '--owner=' . $config->owner->id,
            '--no-interaction',
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

        while ($processes) {
            foreach ($processes as $i => $process) {
                $output = $process->getIncrementalOutput();

                if ($output !== '') {
                    $buffers[$i] .= $output;

                    while (($newlinePos = strpos($buffers[$i], "\n")) !== false) {
                        $line = substr($buffers[$i], 0, $newlinePos);
                        $buffers[$i] = substr($buffers[$i], $newlinePos + 1);

                        $this->handleResultLine($line, $results, $onProgress);
                    }
                }

                if (!$process->isRunning()) {
                    if (trim($buffers[$i]) !== '') {
                        $this->handleResultLine($buffers[$i], $results, $onProgress);
                    }

                    unset($processes[$i], $buffers[$i]);
                }
            }

            if ($processes) {
                usleep(50_000); // 50ms
            }
        }

        return $results;
    }

    private function handleResultLine(string $json, ScanResultCollection $results, ?callable $onProgress): void
    {
        $result = $this->deserializeResult($json);

        if ($result) {
            $results->add($result);

            if ($onProgress) {
                $onProgress($result);
            }
        }
    }

    private function deserializeResult(string $json): ?ScanResult
    {
        $data = json_decode(trim($json), true);

        if (!is_array($data) || !isset($data['path'], $data['type'])) {
            return null;
        }

        $type = ScanResultType::tryFrom($data['type']);

        if (!$type) {
            return null;
        }

        return match ($type) {
            ScanResultType::SUCCESS => ScanResult::success($data['path']),
            ScanResultType::SKIPPED => ScanResult::skipped($data['path']),
            ScanResultType::ERROR => ScanResult::error($data['path'], $data['error'] ?? null),
        };
    }
}
