<?php

namespace App\Values;

use App\Http\Requests\API\Album\AlbumUpdateRequest;
use Illuminate\Contracts\Support\Arrayable;

final class AlbumUpdateData implements Arrayable
{
    private function __construct(public string $name, public ?int $year, public ?string $cover)
    {
    }

    public static function fromRequest(AlbumUpdateRequest $request): self
    {
        return new self(
            name: $request->name,
            year: $request->year ?: null,
            cover: $request->cover ?: null,
        );
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
