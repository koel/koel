<?php

namespace App\Http\Integrations\MusicBrainz\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetReleaseGroupUrlRelationshipsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $mbid)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/release-group/{$this->mbid}";
    }

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'inc' => 'url-rels',
        ];
    }
}
