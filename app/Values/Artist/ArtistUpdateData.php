<?php

namespace App\Values\Artist;

use Illuminate\Contracts\Support\Arrayable;

final readonly class ArtistUpdateData implements Arrayable
{
    private function __construct(
        public string $name,
        public ?string $image,
        public ?string $description,
    ) {}

    /**
     * @param ?string $description null leaves the description unchanged; an empty string removes it
     */
    public static function make(string $name, ?string $image = null, ?string $description = null): self
    {
        return new self(name: $name, image: $image, description: $description);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'cover' => $this->image,
        ];
    }
}
