<?php

namespace App\Values;

use App\Enums\ImageChangeType;
use Illuminate\Support\Arr;

final readonly class ImageChange
{
    private function __construct(
        public ImageChangeType $type,
        public ?string $fileName,
    ) {}

    public static function make(ImageChangeType $type, ?string $fileName = null): self
    {
        return new self(type: $type, fileName: $fileName);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public function applyToAttributes(array $attributes, string $attribute): array
    {
        if ($this->type === ImageChangeType::KEEP) {
            return Arr::except($attributes, $attribute);
        }

        return [...$attributes, $attribute => $this->fileName ?? ''];
    }
}
