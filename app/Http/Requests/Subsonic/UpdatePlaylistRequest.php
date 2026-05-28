<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

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
            'songIdToAdd' => (array) $this->input('songIdToAdd', []),
            'songIndexToRemove' => array_map('intval', (array) $this->input('songIndexToRemove', [])),
        ]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'playlistId' => ['required', 'string'],
            'name' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
