<?php

namespace App\Services;

use Symfony\Component\Process\Process;

// @mago-ignore lint:kan-defect
class ParallelPodcastSync
{
    /** @return array<object> */
    public function execute(array $ids, int $jobs): array
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

        return $this->collectResults($processes);
    }

    /** @param Process[] $processes */
    private function collectResults(array $processes): array
    {
        $results = [];
        $buffers = array_fill(0, count($processes), '');
        $errBuffers = array_fill(0, count($processes), '');

        while ($processes) {
            foreach ($processes as $i => $process) {
                $this->drainOutput($process, $buffers[$i], $results);
                $this->drainErrorOutput($process, $errBuffers[$i]);

                if ($process->isRunning()) {
                    continue;
                }

                $this->finalizeWorker($process, $buffers[$i], $errBuffers[$i], $results);
                unset($processes[$i], $buffers[$i], $errBuffers[$i]);
            }

            if ($processes) {
                usleep(50_000);
            }
        }

        return $results;
    }

    private function drainOutput(Process $process, string &$buffer, array &$results): void
    {
        $output = $process->getIncrementalOutput();

        if ($output === '') {
            return;
        }

        $buffer .= $output;

        while (($pos = strpos($buffer, "\n")) !== false) {
            $this->parseLine(substr($buffer, 0, $pos), $results);
            $buffer = substr($buffer, $pos + 1);
        }
    }

    private function drainErrorOutput(Process $process, string &$buffer): void
    {
        $output = $process->getIncrementalErrorOutput();

        if ($output !== '') {
            $buffer .= $output;
        }
    }

    private function finalizeWorker(Process $process, string $buffer, string $errBuffer, array &$results): void
    {
        if (trim($buffer) !== '') {
            $this->parseLine($buffer, $results);
        }

        if (trim($errBuffer) !== '') {
            $results[] = (object) ['status' => 'error', 'title' => '', 'error' => trim($errBuffer)];
        }

        $exitCode = $process->getExitCode();

        if ($exitCode !== 0 && $exitCode !== null) {
            $results[] = (object) [
                'status' => 'error',
                'title' => '',
                'error' => sprintf('Worker exited with code %d', $exitCode),
            ];
        }
    }

    private function parseLine(string $line, array &$results): void
    {
        $data = json_decode(trim($line));

        if ($data) {
            $results[] = $data;
        }
    }
}
