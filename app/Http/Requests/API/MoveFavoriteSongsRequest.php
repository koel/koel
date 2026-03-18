<?php

namespace App\Http\Requests\API;

use App\Enums\Placement;
use Illuminate\Validation\Rules\Enum;

/**
 * @property-read array<string> $songs
 * @property-read string $target
 * @property-read string $placement
 */
class MoveFavoriteSongsRequest extends Request
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|array|exists:songs,id',
            'target' => 'required|exists:favorites,favoriteable_id',
            'placement' => ['required', new Enum(Placement::class)],
        ];
    }
}
