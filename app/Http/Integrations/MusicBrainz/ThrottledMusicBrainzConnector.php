<?php

namespace App\Http\Integrations\MusicBrainz;

use Saloon\Http\PendingRequest;

/**
 * MusicBrainz asks for no more than one request per second. Nothing enforces that while entries are
 * fetched one at a time as they are browsed, but a backfill walks the library as fast as the network
 * allows, which is how a client gets throttled or blocked. Bind this in place of the plain connector
 * for the duration of such a run.
 */
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
