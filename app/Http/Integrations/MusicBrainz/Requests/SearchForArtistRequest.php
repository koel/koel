<?php

namespace App\Http\Integrations\MusicBrainz\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchForArtistRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $name)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/artist';
    }

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'query' => "artist:{$this->name}",
            'limit' => 1,
        ];
    }
}
