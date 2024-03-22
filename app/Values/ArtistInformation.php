<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;

final class ArtistInformation implements Arrayable
{
    public const JSON_STRUCTURE = [
        'url',
        'image',
        'bio' => [
            'summary',
            'full',
        ],
    ];

    private function __construct(public ?string $url, public ?string $image, public array $bio)
    {
    }

    public static function make(
        ?string $url = null,
        ?string $image = null,
        array $bio = ['summary' => '', 'full' => '']
    ): self {
        return new self($url, $image, $bio);
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'image' => $this->image,
            'bio' => $this->bio,
        ];
    }
}
