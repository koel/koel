<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;

final class ArtistInformation implements Arrayable
{
    use FormatsLastFmText;

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

    public static function fromLastFmData(object $data): self
    {
        return self::make(
            url: $data->url,
            bio: [
                'summary' => isset($data->bio) ? self::formatLastFmText($data->bio->summary) : '',
                'full' => isset($data->bio) ? self::formatLastFmText($data->bio->content) : '',
            ],
        );
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
