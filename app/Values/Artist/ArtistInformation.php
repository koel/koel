<?php

namespace App\Values\Artist;

use App\Services\RichTextSanitizer;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

final class ArtistInformation implements Arrayable
{
    public const array JSON_STRUCTURE = [
        'url',
        'image',
        'bio' => [
            'summary',
            'full',
        ],
    ];

    private function __construct(
        public ?string $url,
        public ?string $image,
        public array $bio,
    ) {
        $sanitizer = new RichTextSanitizer();

        $this->bio['summary'] = $sanitizer->sanitize($this->bio['summary']) ?? '';
        $this->bio['full'] = $sanitizer->sanitize($this->bio['full']) ?? '';
    }

    public static function make(
        ?string $url = null,
        ?string $image = null,
        array $bio = ['summary' => '', 'full' => ''],
    ): self {
        return new self($url, $image, $bio);
    }

    public function withDescription(string $description): self
    {
        return new self($this->url, $this->image, ['summary' => $description, 'full' => $description]);
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
                'summary' => Arr::get($summary, 'extract', ''),
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
