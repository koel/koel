<?php

namespace App\Http\Integrations\iTunes\Requests;

use App\Models\Album;
use App\Models\Artist;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetTrackRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $trackName, private readonly Album $album)
    {
    }

    /** @return array<mixed> */
    protected function defaultQuery(): array
    {
        $term = $this->trackName;

        if ($this->album->name !== Album::UNKNOWN_NAME) {
            $term .= ' ' . $this->album->name;
        }

        if (
            $this->album->artist->name !== Artist::UNKNOWN_NAME
            && $this->album->artist->name !== Artist::VARIOUS_NAME
        ) {
            $term .= ' ' . $this->album->artist->name;
        }

        return [
            'term' => $term,
            'media' => 'music',
            'entity' => 'song',
            'limit' => 1,
        ];
    }

    public function resolveEndpoint(): string
    {
        return '/';
    }
}
