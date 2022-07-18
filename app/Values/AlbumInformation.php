<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;

final class AlbumInformation implements Arrayable
{
    use FormatsLastFmText;

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

    public static function fromLastFmData(object $data): self
    {
        return self::make(
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
