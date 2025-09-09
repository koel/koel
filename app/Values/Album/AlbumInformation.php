<?php

namespace App\Values\Album;

use HTMLPurifier;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

final class AlbumInformation implements Arrayable
{
    public const JSON_STRUCTURE = [
        'url',
        'cover',
        'wiki' => [
            'summary',
            'full',
        ],
        'tracks' => [
            '*' => [
                'title',
                'length',
                'url',
            ],
        ],
    ];

    private function __construct(public ?string $url, public ?string $cover, public array $wiki, public array $tracks)
    {
        $purifier = new HTMLPurifier();

        $this->wiki['summary'] = $purifier->purify($this->wiki['summary']);
        $this->wiki['full'] = $purifier->purify($this->wiki['full']);
    }

    public static function make(
        ?string $url = null,
        ?string $cover = null,
        array $wiki = ['summary' => '', 'full' => ''],
        array $tracks = []
    ): self {
        return new self($url, $cover, $wiki, $tracks);
    }

    public static function fromWikipediaSummary(array $summary): self
    {
        return new self(
            url: Arr::get($summary, 'content_urls.desktop.page'),
            cover: Arr::get($summary, 'thumbnail.source'),
            wiki: [
                'summary' =>  Arr::get($summary, 'extract', ''),
                'full' => Arr::get($summary, 'extract_html', ''),
            ],
            tracks: [],
        );
    }

    public function withMusicBrainzTracks(array $tracks): self
    {
        $self = clone $this;

        $self->tracks = collect($tracks)->map(static function (array $track) {
            return [
                'title' => Arr::get($track, 'title'),
                'length' => (int) Arr::get($track, 'length', 0) / 1000, // MusicBrainz length is in milliseconds
                'url' => 'https://musicbrainz.org/recording/' . Arr::get($track, 'id'),
            ];
        })->toArray();

        return $self;
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'cover' => $this->cover,
            'wiki' => $this->wiki,
            'tracks' => $this->tracks,
        ];
    }
}
