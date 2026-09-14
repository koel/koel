<?php

namespace App\Http\Integrations\MusicBrainz;

use Illuminate\Support\Sleep;
use Saloon\Http\PendingRequest;

final class ThrottledMusicBrainzConnector extends MusicBrainzConnector
{
    /** @link https://musicbrainz.org/doc/MusicBrainz_API/Rate_Limiting */
    private const float REQUEST_INTERVAL_IN_SECONDS = 1.0;

    private float $previousRequestAt = 0.0;

    public function __construct(
        private readonly float $minimumIntervalInSeconds = self::REQUEST_INTERVAL_IN_SECONDS,
    ) {}

    public function boot(PendingRequest $pendingRequest): void
    {
        $elapsed = microtime(true) - $this->previousRequestAt;

        if ($elapsed < $this->minimumIntervalInSeconds) {
            Sleep::usleep((int) round(($this->minimumIntervalInSeconds - $elapsed) * 1_000_000));
        }

        $this->previousRequestAt = microtime(true);
    }
}
