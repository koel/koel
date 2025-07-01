<?php

namespace App\Http\Integrations\MusicBrainz\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchForReleaseRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $albumName, private readonly string $artistName)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/release';
    }

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'query' => "release:\"{$this->albumName}\" AND artist:\"{$this->artistName}\"",
            'limit' => 1,
        ];
    }
}
