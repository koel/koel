<?php

namespace App\Http\Integrations\MusicBrainz\Requests;

use App\Helpers\LuceneQuery;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchForReleaseRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $albumName,
        private readonly string $artistName,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/release';
    }

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'query' => sprintf(
                'release:%s AND artist:%s',
                LuceneQuery::phrase($this->albumName),
                LuceneQuery::phrase($this->artistName),
            ),
            'limit' => 1,
        ];
    }
}
