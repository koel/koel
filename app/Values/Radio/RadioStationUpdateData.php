<?php

namespace App\Values\Radio;

use Illuminate\Contracts\Support\Arrayable;

final readonly class RadioStationUpdateData implements Arrayable
{
    public function __construct(
        public string $name,
        public string $url,
        public string $description,
        public ?string $logo,
        public bool $isPublic,
    ) {
    }

    public static function make(
        string $name,
        string $url,
        string $description,
        ?string $logo = null,
        bool $isPublic = false,
    ): self {
        return new self(
            name: $name,
            url: $url,
            description: $description,
            logo: $logo,
            isPublic: $isPublic,
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'logo' => $this->logo,
            'is_public' => $this->isPublic,
        ];
    }
}
