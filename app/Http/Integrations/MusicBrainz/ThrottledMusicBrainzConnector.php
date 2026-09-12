<?php

namespace App\Http\Integrations\MusicBrainz;

use Saloon\Http\PendingRequest;

/** @link https://musicbrainz.org/doc/MusicBrainz_API/Rate_Limiting */
final class ThrottledMusicBrainzConnector extends MusicBrainzConnector
{
    private float $previousRequestAt = 0.0;

    public function __construct(
        private readonly float $minimumIntervalInSeconds,
    ) {}

    public function boot(PendingRequest $pendingRequest): void
    {
        $elapsed = microtime(true) - $this->previousRequestAt;

        if ($elapsed < $this->minimumIntervalInSeconds) {
            usleep((int) round(($this->minimumIntervalInSeconds - $elapsed) * 1_000_000));
        }

        $this->previousRequestAt = microtime(true);
    }
}
