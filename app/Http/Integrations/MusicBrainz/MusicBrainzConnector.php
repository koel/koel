<?php

namespace App\Http\Integrations\MusicBrainz;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class MusicBrainzConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.services.musicbrainz.endpoint');
    }

    /** @inheritdoc */
    public function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => config('koel.services.musicbrainz.user_agent')
                ?: config('app.name') . '/' . koel_version() . ' ( ' . config('app.url') . ' )',
        ];
    }
}
