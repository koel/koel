<?php

namespace App\Services;

use Closure;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class WorkerProcessPool
{
    private const int POLL_INTERVAL_MICROSECONDS = 50_000;

    /**
     * Runs the commands in parallel and reports each worker's non-blank output lines as they arrive.
     * Any exception thrown from a callback stops the remaining workers and is rethrown.
     *
     * @param array<array<string>> $commands
     * @param Closure(string): void $onOutputLine
     * @param Closure(?int, string, bool): void $onWorkerFailure receives the exit code, the whole error output,
     *                                             and whether the worker was stopped for exceeding a timeout
     * @param (Closure(string): void)|null $onErrorLine
     */
    public function run(
        array $commands,
        ?float $timeout,
        Closure $onOutputLine,
        Closure $onWorkerFailure,
        ?Closure $onErrorLine = null,
        ?float $idleTimeout = null,
    ): void {
        $processes = [];

        foreach ($commands as $command) {
            $process = new Process($command, base_path());
            $process->setTimeout($timeout);
            $process->setIdleTimeout($idleTimeout);
            $process->start();
            $processes[] = $process;
        }

        $outputBuffers = array_fill(0, count($processes), '');
        $errorBuffers = array_fill(0, count($processes), '');

        try {
            while ($processes) {
                foreach ($processes as $i => $process) {
                    $this->emitCompleteLines($process->getIncrementalOutput(), $outputBuffers[$i], $onOutputLine);
                    $this->emitCompleteLines($process->getIncrementalErrorOutput(), $errorBuffers[$i], $onErrorLine);

                    $timedOut = $this->stopIfTimedOut($process);

                    if (!$timedOut && $process->isRunning()) {
                        continue;
                    }

                    $this->emitLine(trim($outputBuffers[$i]), $onOutputLine);
                    $this->emitLine(trim($errorBuffers[$i]), $onErrorLine);

                    if ($timedOut || !$process->isSuccessful()) {
                        $onWorkerFailure($process->getExitCode(), $process->getErrorOutput(), $timedOut);
                    }

                    unset($processes[$i], $outputBuffers[$i], $errorBuffers[$i]);
                }

                if ($processes) {
                    usleep(self::POLL_INTERVAL_MICROSECONDS);
                }
            }
        } catch (Throwable $e) {
            foreach ($processes as $process) {
                $process->stop(1);
            }

            throw $e;
        }
    }

    private function stopIfTimedOut(Process $process): bool
    {
        try {
            $process->checkTimeout();

            return false;
        } catch (ProcessTimedOutException) {
            return true;
        }
    }

    private function emitCompleteLines(string $output, string &$buffer, ?Closure $onLine): void
    {
        $buffer .= $output;

        while (($newlinePosition = strpos($buffer, "\n")) !== false) {
            $line = rtrim(substr($buffer, 0, $newlinePosition));
            $buffer = substr($buffer, $newlinePosition + 1);

            $this->emitLine($line, $onLine);
        }
    }

    private function emitLine(string $line, ?Closure $onLine): void
    {
        if ($line !== '' && $onLine) {
            $onLine($line);
        }
    }
}
