<?php

namespace App\Values\Artist;

use Illuminate\Contracts\Support\Arrayable;

final class ArtistUpdateData implements Arrayable
{
    private function __construct(public string $name, public ?string $image)
    {
    }

    public static function make(string $name, ?string $image = null): self
    {
        return new self(name: $name, image: $image);
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
