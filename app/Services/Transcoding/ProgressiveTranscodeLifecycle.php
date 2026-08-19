<?php

namespace App\Services\Transcoding;

use App\Values\ProgressiveTranscodeSource;
use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\File;
use Throwable;

final class ProgressiveTranscodeLifecycle
{
    private ?ProgressiveTranscodeSource $source = null;

    private ?bool $previousIgnoreUserAbort = null;

    private ?Closure $publication = null;

    private bool $terminated = false;

    public function __construct(
        private readonly ProgressiveTranscodeCapture $capture,
        private readonly ?Lock $lock,
        private readonly ClientConnection $clientConnection,
    ) {}

    public function setSource(ProgressiveTranscodeSource $source): void
    {
        $this->source = $source;
    }

    public function keepRunningAfterDisconnect(): void
    {
        $this->previousIgnoreUserAbort = $this->clientConnection->keepRunningAfterDisconnect();
    }

    public function publishAtTermination(Closure $publication): void
    {
        $this->publication = $publication;
    }

    public function terminate(): void
    {
        if ($this->terminated) {
            return;
        }

        $this->terminated = true;

        try {
            ($this->publication ?? static function (): void {})();
        } catch (Throwable $e) {
            report($e);
        } finally {
            $this->cleanupResources();
        }
    }

    public function cleanup(): void
    {
        if ($this->terminated) {
            return;
        }

        $this->terminated = true;
        $this->cleanupResources();
    }

    private function cleanupResources(): void
    {
        try {
            $this->capture->cleanup();
        } finally {
            try {
                if ($this->source?->temporary) {
                    File::delete($this->source->path);
                }
            } finally {
                try {
                    $this->lock?->release();
                } finally {
                    if ($this->previousIgnoreUserAbort !== null) {
                        $this->clientConnection->restoreAbortBehavior($this->previousIgnoreUserAbort);
                    }
                }
            }
        }
    }
}
