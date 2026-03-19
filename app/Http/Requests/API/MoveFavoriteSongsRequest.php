<?php

namespace App\Http\Requests\API;

use App\Enums\Placement;
use Illuminate\Validation\Rule;
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
            'songs' => [
                'required',
                'array',
            ],
            'songs.*' => [
                Rule::exists('favorites', 'favoriteable_id')
                    ->where('user_id', $this->user()->id)
                    ->where('favoriteable_type', 'playable'),
            ],
            'target' => [
                'required',
                Rule::exists('favorites', 'favoriteable_id')
                    ->where('user_id', $this->user()->id)
                    ->where('favoriteable_type', 'playable'),
                Rule::notIn($this->input('songs', [])),
            ],
            'placement' => ['required', new Enum(Placement::class)],
        ];
    }
}
