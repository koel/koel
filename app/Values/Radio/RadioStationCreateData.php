<?php

namespace App\Values\Radio;

use Illuminate\Contracts\Support\Arrayable;

final readonly class RadioStationCreateData implements Arrayable
{
    private function __construct(
        public string $url,
        public string $name,
        public string $description,
        public ?string $logo,
        public bool $isPublic,
        public ?string $homepageUrl,
    ) {}

    public static function make(
        string $url,
        string $name,
        string $description,
        ?string $logo = null,
        bool $isPublic = false,
        ?string $homepageUrl = null,
    ): self {
        return new self(
            url: $url,
            name: $name,
            description: $description,
            logo: $logo,
            isPublic: $isPublic,
            homepageUrl: $homepageUrl,
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'name' => $this->name,
            'logo' => $this->logo,
            'description' => $this->description,
            'is_public' => $this->isPublic,
            'homepage_url' => $this->homepageUrl,
        ];
    }
}
