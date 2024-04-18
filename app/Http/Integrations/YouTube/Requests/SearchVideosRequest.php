<?php

namespace App\Http\Integrations\YouTube\Requests;

use App\Models\Song;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchVideosRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly Song $song, private readonly string $pageToken = '')
    {
    }

    public function resolveEndpoint(): string
    {
        return '/search';
    }

    /** @return array<mixed> */
    protected function defaultQuery(): array
    {
        $q = $this->song->title;

        // If the artist is worth noticing, include them into the search.
        if (!$this->song->artist->is_unknown && !$this->song->artist->is_various) {
            $q .= " {$this->song->artist->name}";
        }

        return [
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => 10,
            'pageToken' => $this->pageToken,
            'q' => $q,
        ];
    }
}
