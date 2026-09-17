<?php

namespace App\Http\Integrations\MusicBrainz;

use App\Http\Integrations\Concerns\ThrowsWhenUnavailable;
use App\Services\Integrations\MusicBrainzService;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class MusicBrainzConnector extends Connector
{
    use AcceptsJson;
    use ThrowsWhenUnavailable;

    public function resolveBaseUrl(): string
    {
        return config('koel.services.musicbrainz.endpoint');
    }

    /** @inheritdoc */
    public function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => MusicBrainzService::userAgent(),
        ];
    }
}
