<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\URL;

final class Branding implements Arrayable
{
    private function __construct(
        public readonly string $name,
        public ?string $logo,
        public ?string $cover,
    ) {
        if ($logo && !URL::isValidUrl($logo)) {
            $this->logo = image_storage_url($logo);
        }

        if ($cover && !URL::isValidUrl($cover)) {
            $this->cover = image_storage_url($cover);
        }
    }

    public static function make(
        ?string $name = null,
        ?string $logo = null,
        ?string $cover = null,
    ): self {
        return new self(
            name: $name ?: config('app.name'),
            logo: $logo,
            cover: $cover,
        );
    }

    public static function fromArray(array $settings): self
    {
        return new self(
            name: $settings['name'] ?? config('app.name'),
            logo: $settings['logo'] ?? null,
            cover: $settings['cover'] ?? null,
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'logo' => $this->logo,
            'cover' => $this->cover,
        ];
    }

    public function withLogo(?string $logo): self
    {
        return new self(
            name: $this->name,
            logo: $logo,
            cover: $this->cover,
        );
    }

    public function withoutLogo(): self
    {
        return new self(
            name: $this->name,
            logo: null,
            cover: $this->cover,
        );
    }

    public function withName(string $name): self
    {
        return new self(
            name: $name,
            logo: $this->logo,
            cover: $this->cover,
        );
    }

    public function withCover(string $cover): self
    {
        return new self(
            name: $this->name,
            logo: $this->logo,
            cover: $cover,
        );
    }

    public function withoutCover(): self
    {
        return new self(
            name: $this->name,
            logo: $this->logo,
            cover: null,
        );
    }
}
