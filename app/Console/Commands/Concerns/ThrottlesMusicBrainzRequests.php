<?php

namespace App\Console\Commands\Concerns;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\ThrottledMusicBrainzConnector;

trait ThrottlesMusicBrainzRequests
{
    /** @link https://musicbrainz.org/doc/MusicBrainz_API/Rate_Limiting */
    private const float MUSICBRAINZ_REQUEST_INTERVAL_IN_SECONDS = 1.0;

    private function throttleMusicBrainzRequests(): void
    {
        $this->laravel->instance(
            MusicBrainzConnector::class,
            new ThrottledMusicBrainzConnector(self::MUSICBRAINZ_REQUEST_INTERVAL_IN_SECONDS),
        );
    }
}
