<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;

final class AlbumInformation implements Arrayable
{
    use FormatsLastFmText;

    public function __construct(
        public ?string $url = null,
        public ?string $cover = null,
        public array $wiki = ['summary' => '', 'full' => ''],
        public array $tracks = []
    ) {
    }

    public static function fromLastFmData(object $data): self
    {
        return new self(
            url: $data->url,
            wiki: [
                'summary' => isset($data->wiki) ? self::formatLastFmText($data->wiki->summary) : '',
                'full' => isset($data->wiki) ? self::formatLastFmText($data->wiki->content) : '',
            ],
            tracks: array_map(static fn ($track): array => [
                'title' => $track->name,
                'length' => (int) $track->duration,
                'url' => $track->url,
            ], $data->tracks?->track ?? []),
        );
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
