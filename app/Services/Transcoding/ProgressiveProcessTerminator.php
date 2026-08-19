<?php

namespace App\Services\Transcoding;

use Illuminate\Contracts\Process\InvokedProcess as InvokedProcessContract;
use Illuminate\Process\InvokedProcess;
use Throwable;

final class ProgressiveProcessTerminator
{
    /** @param callable(string, string): bool $outputHandler */
    public static function stop(InvokedProcessContract $process, callable $outputHandler): void
    {
        if ($process instanceof InvokedProcess) {
            self::stopConcreteProcess($process);

            return;
        }

        try {
            $process->signal(15);
        } catch (Throwable $e) {
            report($e);
        }

        try {
            $process->wait($outputHandler);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function stopConcreteProcess(InvokedProcess $process): void
    {
        try {
            $process->stop(1);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
