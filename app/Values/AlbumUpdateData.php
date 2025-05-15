<?php

namespace App\Values;

use App\Http\Requests\API\AlbumUpdateRequest;
use Illuminate\Contracts\Support\Arrayable;

final class AlbumUpdateData implements Arrayable
{
    private function __construct(public string $name, public ?int $year)
    {
    }

    public static function fromRequest(AlbumUpdateRequest $request): self
    {
        return new self(
            name: $request->name,
            year: $request->year ?: null,
        );
    }

    public static function make(string $name, ?int $year = null): self
    {
        return new self(
            name: $name,
            year: $year,
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'year' => $this->year,
        ];
    }
}
