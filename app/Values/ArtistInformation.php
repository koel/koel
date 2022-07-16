<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;

final class ArtistInformation implements Arrayable
{
    use FormatsLastFmText;

    public function __construct(
        public ?string $url = null,
        public ?string $image = null,
        public array $bio = ['summary' => '', 'full' => '']
    ) {
    }

    public static function fromLastFmData(object $data): self
    {
        return new self(
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
