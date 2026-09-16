<?php

namespace App\Http\Integrations\CoverArtArchive;

use App\Services\Integrations\MusicBrainzService;
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
            'User-Agent' => MusicBrainzService::userAgent(),
        ];
    }
}
