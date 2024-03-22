<?php

namespace App\Http\Integrations\Lastfm\Requests;

use App\Http\Integrations\Lastfm\Concerns\FormatsLastFmText;
use App\Models\Album;
use App\Values\AlbumInformation;
use Illuminate\Support\Arr;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

final class GetAlbumInfoRequest extends Request
{
    use FormatsLastFmText;

    protected Method $method = Method::GET;

    public function __construct(private Album $album)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/';
    }

    /** @return array<mixed> */
    protected function defaultQuery(): array
    {
        return [
            'method' => 'album.getInfo',
            'artist' => $this->album->artist->name,
            'album' => $this->album->name,
            'autocorrect' => 1,
            'format' => 'json',
        ];
    }

    public function createDtoFromResponse(Response $response): ?AlbumInformation
    {
        $album = object_get($response->object(), 'album');

        if (!$album) {
            return null;
        }

        return AlbumInformation::make(
            url: object_get($album, 'url'),
            cover: Arr::get(object_get($album, 'image', []), '0.#text'),
            wiki: [
                'summary' => self::formatLastFmText(object_get($album, 'wiki.summary')),
                'full' => self::formatLastFmText(object_get($album, 'wiki.content')),
            ],
            tracks: array_map(static fn ($track): array => [
                'title' => $track->name,
                'length' => (int) $track->duration,
                'url' => $track->url,
            ], object_get($album, 'tracks.track', []))
        );
    }
}
