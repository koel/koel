<?php

namespace App\Values\Album;

use Illuminate\Contracts\Support\Arrayable;

final class AlbumUpdateData implements Arrayable
{
    private function __construct(public string $name, public ?int $year, public ?string $cover)
    {
    }

    public static function make(string $name, ?int $year = null, ?string $cover = null): self
    {
        return new self(
            name: $name,
            year: $year,
            cover: $cover,
        );
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
