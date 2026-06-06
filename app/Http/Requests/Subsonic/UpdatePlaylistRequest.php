<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Support\Arr;

/**
 * @property string $playlistId
 * @property ?string $name
 * @property ?string $comment
 * @property list<string> $songIdToAdd
 * @property list<int> $songIndexToRemove
 */
class UpdatePlaylistRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'songIdToAdd' => Arr::wrap($this->input('songIdToAdd')),
            'songIndexToRemove' => Arr::wrap($this->input('songIndexToRemove')),
        ]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'playlistId' => ['required', 'string'],
            'name' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'songIdToAdd' => ['array'],
            'songIdToAdd.*' => ['string'],
            'songIndexToRemove' => ['array'],
            'songIndexToRemove.*' => ['integer', 'min:0'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'songIndexToRemove' => array_map('intval', Arr::wrap($this->input('songIndexToRemove'))),
        ]);
    }
}
