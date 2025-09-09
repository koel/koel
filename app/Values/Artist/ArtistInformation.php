<?php

namespace App\Values\Artist;

use HTMLPurifier;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

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
        $purifier = new HTMLPurifier();

        $this->bio['summary'] = $purifier->purify($this->bio['summary']);
        $this->bio['full'] = $purifier->purify($this->bio['full']);
    }

    public static function make(
        ?string $url = null,
        ?string $image = null,
        array $bio = ['summary' => '', 'full' => '']
    ): self {
        return new self($url, $image, $bio);
    }

    /**
     * @param array<string, mixed> $summary
     */
    public static function fromWikipediaSummary(array $summary): self
    {
        return new self(
            url: Arr::get($summary, 'content_urls.desktop.page'),
            image: Arr::get($summary, 'thumbnail.source'),
            bio: [
                'summary' =>  Arr::get($summary, 'extract', ''),
                'full' => Arr::get($summary, 'extract_html', ''),
            ],
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'image' => $this->image,
            'bio' => $this->bio,
        ];
    }
}
