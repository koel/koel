<?php

namespace App\Http\Integrations\MusicBrainz\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetRecordingsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $mbid)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/release/{$this->mbid}";
    }

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'inc' => 'recordings',
        ];
    }
}
