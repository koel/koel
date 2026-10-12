<?php

namespace App\Values\Album;

use Illuminate\Contracts\Support\Arrayable;

final readonly class AlbumUpdateData implements Arrayable
{
    private function __construct(
        public string $name,
        public ?int $year,
        public ?string $cover,
        public ?string $description,
    ) {}

    /**
     * @param ?string $description null leaves the description unchanged; an empty string removes it
     */
    public static function make(
        string $name,
        ?int $year = null,
        ?string $cover = null,
        ?string $description = null,
    ): self {
        return new self(name: $name, year: $year, cover: $cover, description: $description);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'year' => $this->year,
            'cover' => $this->cover,
        ];
    }
}
