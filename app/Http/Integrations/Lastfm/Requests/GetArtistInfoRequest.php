<?php

namespace App\Http\Integrations\Lastfm\Requests;

use App\Http\Integrations\Lastfm\Concerns\FormatsLastFmText;
use App\Models\Artist;
use App\Values\ArtistInformation;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

final class GetArtistInfoRequest extends Request
{
    use FormatsLastFmText;

    protected Method $method = Method::GET;

    public function __construct(private Artist $artist)
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
            'method' => 'artist.getInfo',
            'artist' => $this->artist->name,
            'autocorrect' => 1,
            'format' => 'json',
        ];
    }

    public function createDtoFromResponse(Response $response): ?ArtistInformation
    {
        $artist = object_get($response->object(), 'artist');

        if (!$artist) {
            return null;
        }

        return ArtistInformation::make(
            url: object_get($artist, 'url'),
            bio: [
                'summary' => self::formatLastFmText(object_get($artist, 'bio.summary')),
                'full' => self::formatLastFmText(object_get($artist, 'bio.content')),
            ],
        );
    }
}
