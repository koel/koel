<?php

namespace App\Values;

use App\Http\Requests\API\Artist\ArtistUpdateRequest;
use Illuminate\Contracts\Support\Arrayable;

final class ArtistUpdateData implements Arrayable
{
    private function __construct(public string $name, public ?string $image)
    {
    }

    public static function fromRequest(ArtistUpdateRequest $request): self
    {
        return new self(
            name: $request->name,
            image: $request->image ?: null,
        );
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
