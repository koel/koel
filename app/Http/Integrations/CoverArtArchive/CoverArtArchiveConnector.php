<?php

namespace App\Http\Integrations\CoverArtArchive;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class CoverArtArchiveConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('koel.services.musicbrainz.cover_art_endpoint');
    }

    /** @inheritdoc */
    public function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => config('koel.services.musicbrainz.user_agent') ?: sprintf(
                '%s/%s( %s )',
                config('app.name'),
                koel_version(),
                config('app.url'),
            ),
        ];
    }
}
