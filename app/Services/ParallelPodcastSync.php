<?php

namespace App\Services;

use Closure;
use Symfony\Component\Process\Process;

// @mago-ignore lint:cyclomatic-complexity,kan-defect
class ParallelPodcastSync
{
    /** @param Closure(object): void $onResult */
    public function execute(array $ids, int $jobs, Closure $onResult): void
    {
        $chunks = array_chunk($ids, (int) ceil(count($ids) / $jobs));
        $processes = [];

        foreach ($chunks as $chunk) {
            $command = [PHP_BINARY, base_path('artisan'), 'koel:podcasts:sync-chunk', '--no-interaction', '--no-ansi'];

            foreach ($chunk as $id) {
                $command[] = $id;
            }

            $process = new Process($command, base_path());
            $process->setTimeout(300);
            $process->start();
            $processes[] = $process;
        }

        $this->collectResults($processes, $onResult);
    }

    /** @param Process[] $processes */
    private function collectResults(array $processes, Closure $onResult): void
    {
        $buffers = array_fill(0, count($processes), '');
        $errBuffers = array_fill(0, count($processes), '');

        while ($processes) {
            foreach ($processes as $i => $process) {
                $this->drainOutput($process, $buffers[$i], $onResult);
                $this->drainErrorOutput($process, $errBuffers[$i], $onResult);

                if ($process->isRunning()) {
                    continue;
                }

                $this->finalizeWorker($process, $buffers[$i], $errBuffers[$i], $onResult);
                unset($processes[$i], $buffers[$i], $errBuffers[$i]);
            }

            if ($processes) {
                usleep(50_000);
            }
        }
    }

    private function drainOutput(Process $process, string &$buffer, Closure $onResult): void
    {
        $output = $process->getIncrementalOutput();

        if ($output === '') {
            return;
        }

        $buffer .= $output;

        while (($pos = strpos($buffer, "\n")) !== false) {
            $this->parseLine(substr($buffer, 0, $pos), $onResult);
            $buffer = substr($buffer, $pos + 1);
        }
    }

    private function drainErrorOutput(Process $process, string &$buffer, Closure $onResult): void
    {
        $output = $process->getIncrementalErrorOutput();

        if ($output === '') {
            return;
        }

        $buffer .= $output;

        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = rtrim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);

            if ($line !== '') {
                $onResult((object) ['status' => 'error', 'title' => '', 'error' => $line]);
            }
        }
    }

    private function finalizeWorker(Process $process, string $buffer, string $errBuffer, Closure $onResult): void
    {
        foreach (explode("\n", $buffer) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $this->parseLine($line, $onResult);
        }

        if (trim($errBuffer) !== '') {
            $onResult((object) ['status' => 'error', 'title' => '', 'error' => trim($errBuffer)]);
        }

        $exitCode = $process->getExitCode();

        if ($exitCode !== 0 && $exitCode !== null) {
            $onResult((object) [
                'status' => 'error',
                'title' => '',
                'error' => sprintf('Worker exited with code %d', $exitCode),
            ]);
        }
    }

    private function parseLine(string $line, Closure $onResult): void
    {
        $data = json_decode(trim($line));

        if (is_object($data) && property_exists($data, 'status')) {
            $onResult($data);
        }
    }
}
