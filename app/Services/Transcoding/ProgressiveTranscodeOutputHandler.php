<?php

namespace App\Services\Transcoding;

use Closure;
use Symfony\Component\Process\Process as SymfonyProcess;
use Throwable;

final class ProgressiveTranscodeOutputHandler
{
    private readonly Closure $onAudioChunk;

    private ?Throwable $failure = null;

    private string $errorOutput = '';

    /** @param callable(string): void $onAudioChunk */
    public function __construct(callable $onAudioChunk)
    {
        $this->onAudioChunk = $onAudioChunk(...);
    }

    public function __invoke(string $type, string $output): bool
    {
        if ($this->failure) {
            return true;
        }

        if ($type !== SymfonyProcess::OUT) {
            $this->errorOutput .= $output;

            return false;
        }

        try {
            ($this->onAudioChunk)($output);
        } catch (Throwable $e) {
            $this->failure = $e;

            return true;
        }

        return false;
    }

    public function throwIfFailed(): void
    {
        throw_if($this->failure !== null, $this->failure);
    }

    public function errorOutput(): string
    {
        return $this->errorOutput;
    }
}
