<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;

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
    }

    public static function make(
        ?string $url = null,
        ?string $cover = null,
        array $wiki = ['summary' => '', 'full' => ''],
        array $tracks = []
    ): self {
        return new self($url, $cover, $wiki, $tracks);
    }

    /** @return array<mixed> */
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
